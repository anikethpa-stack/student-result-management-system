<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$info = '';
$err = '';

// Handle adding mark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mark'])) {
    $usn = strtoupper(trim($_POST['usn'] ?? ''));
    $name = trim($_POST['name'] ?? ''));
    $subject = trim($_POST['subject'] ?? '');
    $marks = intval($_POST['marks'] ?? 0);
    
    if ($usn === '' || $name === '' || $subject === '') {
        $err = "Please fill all fields.";
    } elseif ($marks < 0 || $marks > 100) {
        $err = "Marks must be between 0 and 100.";
    } else {
        // Check if student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE usn = ?");
        $stmt->bind_param("s", $usn);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if (!$res->fetch_assoc()) {
            // Create student with default password
            $defaultPassHash = password_hash('changeme', PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO students (usn, name, password) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $usn, $name, $defaultPassHash);
            
            if (!$ins->execute()) {
                $err = "Failed to create student: " . $conn->error;
            }
            $ins->close();
        }
        $stmt->close();
        
        if ($err === '') {
            // Insert result
            $ins2 = $conn->prepare("INSERT INTO results (usn, subject, marks) VALUES (?, ?, ?)");
            $ins2->bind_param("ssi", $usn, $subject, $marks);
            
            if ($ins2->execute()) {
                $info = "Mark added successfully for $name ($usn).";
            } else {
                $err = "Failed to add mark: " . $conn->error;
            }
            $ins2->close();
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    $d = $conn->prepare("DELETE FROM results WHERE id = ?");
    $d->bind_param("i", $delId);
    
    if ($d->execute()) {
        $info = "Record deleted successfully.";
    } else {
        $err = "Failed to delete: " . $conn->error;
    }
    $d->close();
}

// Fetch all results
$resAll = $conn->query("SELECT r.id, r.usn, s.name, r.subject, r.marks, r.created_at 
                        FROM results r 
                        LEFT JOIN students s ON r.usn = s.usn 
                        ORDER BY r.created_at DESC");

// Get statistics
$totalStudents = $conn->query("SELECT COUNT(DISTINCT usn) as count FROM results")->fetch_assoc()['count'] ?? 0;
$totalMarks = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'] ?? 0;
$avgMarks = $conn->query("SELECT AVG(marks) as avg FROM results")->fetch_assoc()['avg'] ?? 0;
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
      background: linear-gradient(135deg, #e0e7ff 0%, #fde2e4 100%);
    }
    .stats-card {
      border-left: 4px solid;
      transition: all 0.3s ease;
    }
    .stats-card:hover {
      transform: translateX(5px);
    }
    .stats-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">
      <span style="font-size: 1.5rem;">👨‍🏫</span>
      Admin Dashboard
    </a>
    <div class="d-flex align-items-center">
      <span class="text-white me-3">
        <small>Welcome, Admin</small>
      </span>
      <a class="btn btn-outline-light btn-sm" href="admin_logout.php">
        🚪 Logout
      </a>
    </div>
  </div>
</nav>

<!-- Statistics Cards -->
<div class="container-fluid py-4">
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card stats-card border-0" style="border-left-color: #667eea !important;">
        <div class="card-body d-flex align-items-center">
          <div class="stats-icon me-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            👥
          </div>
          <div>
            <h6 class="text-muted mb-1">Total Students</h6>
            <h3 class="mb-0 fw-bold"><?php echo $totalStudents; ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stats-card border-0" style="border-left-color: #11998e !important;">
        <div class="card-body d-flex align-items-center">
          <div class="stats-icon me-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            📊
          </div>
          <div>
            <h6 class="text-muted mb-1">Total Records</h6>
            <h3 class="mb-0 fw-bold"><?php echo $totalMarks; ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stats-card border-0" style="border-left-color: #f093fb !important;">
        <div class="card-body d-flex align-items-center">
          <div class="stats-icon me-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            ⭐
          </div>
          <div>
            <h6 class="text-muted mb-1">Average Marks</h6>
            <h3 class="mb-0 fw-bold"><?php echo round($avgMarks, 1); ?>%</h3>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card mb-3 fade-in">
        <div class="card-header bg-primary text-white d-flex align-items-center">
          <span class="me-2" style="font-size: 1.3rem;">➕</span>
          <strong>Add Student Mark</strong>
        </div>
        <div class="card-body">
          <?php if($info): ?>
            <div class="alert alert-success alert-dismissible fade show">
              <strong>✅ Success!</strong> <?php echo htmlspecialchars($info); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <?php if($err): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <strong>❌ Error!</strong> <?php echo htmlspecialchars($err); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <form method="post" novalidate class="needs-validation">
            <div class="mb-3">
              <label class="form-label">
                <strong>USN</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="usn" class="form-control" placeholder="e.g., 1MS21CS001" required>
              <small class="text-muted">University Seat Number</small>
            </div>
            <div class="mb-3">
              <label class="form-label">
                <strong>Student Name</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="name" class="form-control" placeholder="e.g., John Doe" required>
            </div>
            <div class="mb-3">
              <label class="form-label">
                <strong>Subject</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="subject" class="form-control" placeholder="e.g., Mathematics" required>
            </div>
            <div class="mb-3">
              <label class="form-label">
                <strong>Marks</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="marks" type="number" min="0" max="100" class="form-control" placeholder="0-100" required>
              <small class="text-muted">Enter marks out of 100</small>
            </div>
            <div class="d-grid">
              <button name="add_mark" class="btn btn-primary btn-lg">
                ➕ Add Mark
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <div class="alert alert-info border-0">
        <div class="d-flex">
          <div class="me-2" style="font-size: 1.5rem;">💡</div>
          <div>
            <strong>Quick Tip:</strong>
            <small class="d-block mt-1">
              If a student doesn't exist, they will be created automatically with password <code>changeme</code>. 
              Ask them to change it after first login.
            </small>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-lg-7">
      <div class="card fade-in">
        <div class="card-header bg-secondary text-white d-flex align-items-center">
          <span class="me-2" style="font-size: 1.3rem;">📊</span>
          <strong>All Marks</strong>
          <?php if($totalMarks > 0): ?>
            <span class="badge bg-light text-dark ms-auto"><?php echo $totalMarks; ?> records</span>
          <?php endif; ?>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover mb-0">
              <thead style="position: sticky; top: 0; z-index: 10;">
                <tr>
                  <th>USN</th>
                  <th>Name</th>
                  <th>Subject</th>
                  <th>Marks</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($resAll && $resAll->num_rows > 0): ?>
                  <?php while($row = $resAll->fetch_assoc()): ?>
                    <tr>
                      <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['usn']); ?></span></td>
                      <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                      <td><?php echo htmlspecialchars($row['subject']); ?></td>
                      <td>
                        <?php 
                          $marks = (int)$row['marks'];
                          $color = $marks >= 75 ? 'success' : ($marks >= 50 ? 'warning' : 'danger');
                        ?>
                        <span class="badge bg-<?php echo $color; ?> px-3 py-2">
                          <?php echo $marks; ?>/100
                        </span>
                      </td>
                      <td>
                        <form method="post" style="display:inline" onsubmit="return confirm('⚠️ Are you sure you want to delete this record?')">
                          <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                          <button class="btn btn-sm btn-danger">
                            🗑️ Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center py-5">
                      <div style="font-size: 3rem; opacity: 0.3;">📝</div>
                      <p class="text-muted mt-2 mb-0">No marks added yet</p>
                      <small class="text-muted">Add your first student mark using the form</small>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
