<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$info = '';
$err = '';

// Handle bulk marks entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_add'])) {
    $usn = strtoupper(trim($_POST['usn'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    
    if ($usn === '' || $name === '' || $email === '') {
        $err = "USN, Name, and Email are required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM students WHERE usn = ?");
        $stmt->bind_param("s", $usn);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if (!$res->fetch_assoc()) {
            $defaultPassHash = password_hash('changeme', PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO students (usn, name, email, father_name, class, password) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssss", $usn, $name, $email, $father_name, $class, $defaultPassHash);
            $ins->execute();
            $ins->close();
        } else {
            // Update existing student details
            $upd = $conn->prepare("UPDATE students SET name=?, email=?, father_name=?, class=? WHERE usn=?");
            $upd->bind_param("sssss", $name, $email, $father_name, $class, $usn);
            $upd->execute();
            $upd->close();
        }
        $stmt->close();
        
        // Insert/Update marks
        $subjects = $_POST['subject'] ?? [];
        $marks = $_POST['marks'] ?? [];
        $max_marks_arr = $_POST['max_marks'] ?? [];
        $added_count = 0;
        
        foreach ($subjects as $index => $subject) {
            $subject = trim($subject);
            $mark = intval($marks[$index] ?? 0);
            $max_mark = intval($max_marks_arr[$index] ?? 100);
            
            // Validate max_marks (only 50 or 100 allowed)
            if ($max_mark != 50 && $max_mark != 100) {
                $max_mark = 100;
            }
            
            if ($subject !== '' && $mark >= 0 && $mark <= $max_mark) {
                $ins2 = $conn->prepare("INSERT INTO results (usn, subject, marks, max_marks) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE marks = ?, max_marks = ?");
                $ins2->bind_param("ssiiii", $usn, $subject, $mark, $max_mark, $mark, $max_mark);
                if ($ins2->execute()) $added_count++;
                $ins2->close();
            }
        }
        
        if ($added_count > 0) {
            $info = "Successfully added/updated $added_count subject(s) for $name ($usn).";
        } else {
            $err = "No valid marks were added.";
        }
    }
}

// Handle EDIT marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_marks'])) {
    $edit_id = intval($_POST['edit_id']);
    $new_marks = intval($_POST['new_marks']);
    $new_max_marks = intval($_POST['new_max_marks']);
    
    // Validate max_marks
    if ($new_max_marks != 50 && $new_max_marks != 100) {
        $new_max_marks = 100;
    }
    
    if ($new_marks >= 0 && $new_marks <= $new_max_marks) {
        $upd = $conn->prepare("UPDATE results SET marks = ?, max_marks = ? WHERE id = ?");
        $upd->bind_param("iii", $new_marks, $new_max_marks, $edit_id);
        if ($upd->execute()) {
            $info = "Marks updated successfully!";
        } else {
            $err = "Failed to update marks.";
        }
        $upd->close();
    } else {
        $err = "Invalid marks. Marks must be between 0 and $new_max_marks.";
    }
}

// Handle delete single subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $delId = intval($_POST['delete_id']);
    $d = $conn->prepare("DELETE FROM results WHERE id = ?");
    $d->bind_param("i", $delId);
    if ($d->execute()) $info = "Subject deleted successfully.";
    else $err = "Failed to delete: " . $conn->error;
    $d->close();
}

// Handle delete entire student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $delUsn = $_POST['delete_usn'];
    // Delete all results
    $d1 = $conn->prepare("DELETE FROM results WHERE usn = ?");
    $d1->bind_param("s", $delUsn);
    $d1->execute();
    $d1->close();
    
    // Delete student
    $d2 = $conn->prepare("DELETE FROM students WHERE usn = ?");
    $d2->bind_param("s", $delUsn);
    if ($d2->execute()) $info = "Student and all records deleted successfully.";
    else $err = "Failed to delete student: " . $conn->error;
    $d2->close();
}

// Fetch students grouped with their results
$studentsQuery = "SELECT DISTINCT s.usn, s.name, s.email, s.father_name, s.class 
                  FROM students s 
                  LEFT JOIN results r ON s.usn = r.usn 
                  GROUP BY s.usn 
                  ORDER BY s.usn DESC";
$studentsResult = $conn->query($studentsQuery);

$totalStudents = $conn->query("SELECT COUNT(DISTINCT usn) as count FROM results")->fetch_assoc()['count'] ?? 0;
$totalMarks = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
    }
    .excel-table {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(212, 175, 55, 0.2);
    }
    .excel-table thead {
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      color: #000;
      font-weight: 600;
    }
    .excel-row {
      border-bottom: 1px solid #f0f0f0;
    }
    .excel-input {
      border: 1px solid #e0e0e0;
      border-radius: 5px;
      padding: 0.5rem;
      width: 100%;
    }
    .excel-input:focus {
      outline: none;
      border-color: #d4af37;
      box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
    }
    .add-row-btn {
      background: #d4af37;
      color: #000;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }
    .remove-row-btn {
      background: #dc3545;
      color: white;
      border: none;
      padding: 0.3rem 0.6rem;
      border-radius: 5px;
      cursor: pointer;
    }
    .student-card {
      background: white;
      border-radius: 15px;
      margin-bottom: 1rem;
      border: 2px solid #f0f0f0;
      transition: all 0.3s ease;
    }
    .student-card:hover {
      border-color: #d4af37;
      box-shadow: 0 5px 20px rgba(212, 175, 55, 0.2);
    }
    .student-header {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      padding: 1rem 1.5rem;
      border-radius: 13px 13px 0 0;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .student-header:hover {
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
    }
    .student-body {
      padding: 1.5rem;
      display: none;
    }
    .student-body.show {
      display: block;
    }
    .subject-badge {
      background: #f8f9fa;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-left: 4px solid #10b981;
    }
    .no-subjects {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }
    .edit-form {
      display: inline-flex;
      gap: 5px;
      align-items: center;
    }
    .edit-input {
      width: 60px;
      padding: 0.2rem 0.4rem;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background: linear-gradient(135deg, #232526 0%, #414345 100%);">
  <div class="container-fluid">
    <span class="navbar-brand">
      <span style="font-size: 1.5rem;">👨‍🏫</span>
      Admin Dashboard
    </span>
    <div class="d-flex align-items-center gap-3">
      <span class="badge" style="background: #d4af37; color: #000; font-size: 0.9rem;">
        📊 <?php echo $totalStudents; ?> Students | <?php echo $totalMarks; ?> Records
      </span>
      <a class="btn btn-outline-light btn-sm" href="admin_logout.php">
        🚪 Logout
      </a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <?php if($info): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>✅ Success!</strong> <?php echo htmlspecialchars($info); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>❌ Error!</strong> <?php echo htmlspecialchars($err); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Left Panel - Entry Form -->
    <div class="col-lg-5 mb-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); color: #000 !important;">
          <strong>➕ Add/Update Student Marks</strong>
        </div>
        <div class="card-body">
          <form method="post">
            <h6 class="mb-3">👤 Student Information</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">USN (University Seat Number) <span class="text-danger">*</span></label>
                <input name="usn" id="usn_input" class="form-control" placeholder="e.g., 1MS21CS001" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">Email <span class="text-danger">*</span></label>
                <input name="email" type="email" class="form-control" placeholder="student@email.com" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                <input name="name" class="form-control" placeholder="Student Name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">Father's Name</label>
                <input name="father_name" class="form-control" placeholder="Father's name">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">Class</label>
                <input name="class" class="form-control" placeholder="e.g., 6">
              </div>
            </div>

            <hr>
            <h6 class="mb-3">📚 Subjects & Marks</h6>

            <div class="excel-table">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th width="5%">#</th>
                    <th width="45%">Subject Name</th>
                    <th width="20%">Marks</th>
                    <th width="20%">Max Marks</th>
                    <th width="10%"></th>
                  </tr>
                </thead>
                <tbody id="marksTable">
                  <tr class="excel-row">
                    <td class="text-center">1</td>
                    <td><input type="text" name="subject[]" class="excel-input" placeholder="e.g., ODIA"></td>
                    <td><input type="number" name="marks[]" class="excel-input" min="0" max="100" placeholder="0-100"></td>
                    <td>
                      <select name="max_marks[]" class="excel-input">
                        <option value="50">50</option>
                        <option value="100" selected>100</option>
                      </select>
                    </td>
                    <td></td>
                  </tr>
                  <tr class="excel-row">
                    <td class="text-center">2</td>
                    <td><input type="text" name="subject[]" class="excel-input" placeholder="e.g., ENGLISH"></td>
                    <td><input type="number" name="marks[]" class="excel-input" min="0" max="100" placeholder="0-100"></td>
                    <td>
                      <select name="max_marks[]" class="excel-input">
                        <option value="50">50</option>
                        <option value="100" selected>100</option>
                      </select>
                    </td>
                    <td></td>
                  </tr>
                  <tr class="excel-row">
                    <td class="text-center">3</td>
                    <td><input type="text" name="subject[]" class="excel-input" placeholder="e.g., MATHEMATICS"></td>
                    <td><input type="number" name="marks[]" class="excel-input" min="0" max="100" placeholder="0-100"></td>
                    <td>
                      <select name="max_marks[]" class="excel-input">
                        <option value="50">50</option>
                        <option value="100" selected>100</option>
                      </select>
                    </td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button type="button" class="add-row-btn" onclick="addRow()">➕ Add More Subjects</button>
              <button type="submit" name="bulk_add" class="btn btn-success">💾 Save All Marks</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Panel - Students List -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #8B7355 0%, #5D4E37 100%);">
          <strong>👥 All Students (<?php echo $totalStudents; ?>)</strong>
          <small>Click on student to expand/collapse</small>
        </div>
        <div class="card-body p-3" style="max-height: 700px; overflow-y: auto;">
          <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
            <?php while($student = $studentsResult->fetch_assoc()): ?>
              <?php
                $usn = $student['usn'];
                $subjectsQuery = $conn->prepare("SELECT id, subject, marks, max_marks FROM results WHERE usn = ? ORDER BY subject ASC");
                $subjectsQuery->bind_param("s", $usn);
                $subjectsQuery->execute();
                $subjectsResult = $subjectsQuery->get_result();
                $subjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);
                $subjectsQuery->close();
                
                $totalMarksStudent = array_sum(array_column($subjects, 'marks'));
                $totalMaxMarks = array_sum(array_column($subjects, 'max_marks'));
                $avgMarks = $totalMaxMarks > 0 ? round(($totalMarksStudent / $totalMaxMarks) * 100, 1) : 0;
              ?>
              
              <div class="student-card" id="student-<?php echo htmlspecialchars($usn); ?>">
                <!-- Student Header -->
                <div class="student-header" onclick="toggleStudent('<?php echo htmlspecialchars($usn); ?>')">
                  <div>
                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($student['name']); ?></h6>
                    <small>
                      <strong>USN:</strong> <?php echo htmlspecialchars($usn); ?> | 
                      <strong>Class:</strong> <?php echo htmlspecialchars($student['class'] ?? 'N/A'); ?> |
                      <strong>Subjects:</strong> <?php echo count($subjects); ?> |
                      <strong>Avg:</strong> <?php echo $avgMarks; ?>%
                    </small>
                  </div>
                  <div>
                    <span class="badge bg-dark"><?php echo count($subjects); ?> subjects</span>
                  </div>
                </div>
                
                <!-- Student Body (Expandable) -->
                <div class="student-body" id="body-<?php echo htmlspecialchars($usn); ?>">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <small class="text-muted">Email:</small>
                      <div><strong><?php echo htmlspecialchars($student['email']); ?></strong></div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted">Father's Name:</small>
                      <div><strong><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></strong></div>
                    </div>
                  </div>
                  
                  <h6 class="mb-2">📚 Subjects & Marks:</h6>
                  
                  <?php if (count($subjects) > 0): ?>
                    <?php foreach($subjects as $subj): ?>
                      <?php 
                        $mark = (int)$subj['marks'];
                        $max_mark = (int)$subj['max_marks'];
                        $percentage = $max_mark > 0 ? ($mark / $max_mark) * 100 : 0;
                        $color = $percentage >= 75 ? '#10b981' : ($percentage >= 50 ? '#f59e0b' : '#ef4444');
                      ?>
                      <div class="subject-badge" style="border-left-color: <?php echo $color; ?>;">
                        <div>
                          <strong><?php echo htmlspecialchars($subj['subject']); ?></strong>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge" style="background: <?php echo $color; ?>;">
                            <?php echo $mark; ?>/<?php echo $max_mark; ?>
                          </span>
                          
                          <!-- EDIT FORM -->
                          <button class="btn btn-sm btn-warning" style="padding: 0.1rem 0.4rem; font-size: 0.75rem;" 
                                  onclick="showEditForm(<?php echo $subj['id']; ?>, <?php echo $mark; ?>, <?php echo $max_mark; ?>)">
                            ✏️ Edit
                          </button>
                          
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this subject?')">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$subj['id']; ?>">
                            <button type="submit" name="delete_subject" class="btn btn-sm btn-danger" style="padding: 0.1rem 0.4rem; font-size: 0.75rem;">🗑️</button>
                          </form>
                        </div>
                      </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3 pt-3 border-top">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Total: <?php echo $totalMarksStudent; ?> / <?php echo $totalMaxMarks; ?></strong>
                          <span class="ms-3 text-muted">Average: <?php echo $avgMarks; ?>%</span>
                        </div>
                        <form method="post" style="display:inline;" onsubmit="return confirm('⚠️ Delete entire student and all their marks?')">
                          <input type="hidden" name="delete_usn" value="<?php echo htmlspecialchars($usn); ?>">
                          <button type="submit" name="delete_student" class="btn btn-sm btn-danger">🗑️ Delete Student</button>
                        </form>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="no-subjects">
                      <p>📭 No subjects added yet</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-5">
              <div style="font-size: 3rem; opacity: 0.2;">👥</div>
              <p class="text-muted mt-2">No students added yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);">
        <h5 class="modal-title">✏️ Edit Marks</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="mb-3">
            <label class="form-label"><strong>New Marks</strong></label>
            <input type="number" name="new_marks" id="new_marks" class="form-control" min="0" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><strong>Max Marks</strong></label>
            <select name="new_max_marks" id="new_max_marks" class="form-control" required>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_marks" class="btn btn-success">💾 Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let rowCount = 3;
function addRow() {
  rowCount++;
  const table = document.getElementById('marksTable');
  const row = document.createElement('tr');
  row.className = 'excel-row';
  row.innerHTML = `
    <td class="text-center">${rowCount}</td>
    <td><input type="text" name="subject[]" class="excel-input" placeholder="Subject Name"></td>
    <td><input type="number" name="marks[]" class="excel-input" min="0" max="100" placeholder="0-100"></td>
    <td>
      <select name="max_marks[]" class="excel-input">
        <option value="50">50</option>
        <option value="100" selected>100</option>
      </select>
    </td>
    <td><button type="button" class="remove-row-btn" onclick="removeRow(this)">✕</button></td>
  `;
  table.appendChild(row);
}

function removeRow(btn) {
  btn.closest('tr').remove();
}

function toggleStudent(usn) {
  const body = document.getElementById('body-' + usn);
  body.classList.toggle('show');
}

function showEditForm(id, currentMarks, currentMaxMarks) {
  document.getElementById('edit_id').value = id;
  document.getElementById('new_marks').value = currentMarks;
  document.getElementById('new_max_marks').value = currentMaxMarks;
  document.getElementById('new_marks').max = currentMaxMarks;
  
  const modal = new bootstrap.Modal(document.getElementById('editModal'));
  modal.show();
}

// Auto-uppercase USN
document.getElementById('usn_input').addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});

// Update marks max based on max_marks selection
document.getElementById('new_max_marks').addEventListener('change', function() {
  document.getElementById('new_marks').max = this.value;
});
</script>
</body>
</html>