<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_logged_in'])) {
    header("Location: student_login.php");
    exit;
}

$session_usn = $_SESSION['student_usn'];
$usn = strtoupper(trim($_GET['usn'] ?? ''));

// Security: Ensure student can only view their own USN
if ($usn === '' || $usn !== $session_usn) {
    die("❌ Unauthorized access. You can only view your own results.");
}

// Fetch results
$stmt = $conn->prepare("SELECT subject, marks FROM results WHERE usn = ? ORDER BY subject ASC");
$stmt->bind_param("s", $usn);
$stmt->execute();
$res = $stmt->get_result();

$rows = $res->fetch_all(MYSQLI_ASSOC);
$total = 0;
$maxTotal = 0;

foreach ($rows as $r) {
    $total += (int)$r['marks'];
    $maxTotal += 100;
}

$percentage = $maxTotal > 0 ? round(($total / $maxTotal) * 100, 2) : 0;

// Determine grade
if ($percentage >= 90) $grade = "A+";
elseif ($percentage >= 80) $grade = "A";
elseif ($percentage >= 70) $grade = "B+";
elseif ($percentage >= 60) $grade = "B";
elseif ($percentage >= 50) $grade = "C";
else $grade = "F";

$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Result - <?php echo htmlspecialchars($usn); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #e0e7ff 0%, #fde2e4 100%);
    }
    #printArea {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      border: 3px solid #667eea;
    }
    .result-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 2rem;
      border-radius: 17px 17px 0 0;
      position: relative;
      overflow: hidden;
    }
    .result-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }
    .grade-badge {
      width: 100px;
      height: 100px;
      background: white;
      color: #667eea;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      font-weight: bold;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      margin: 0 auto;
    }
    .mark-row {
      transition: all 0.3s ease;
    }
    .mark-row:hover {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    }
  </style>
</head>
<body>
<div class="container py-5">
  <div id="printArea" class="fade-in">
    <?php if (count($rows) === 0): ?>
      <!-- No Records Found -->
      <div class="p-5 text-center">
        <div style="font-size: 5rem; opacity: 0.2; margin-bottom: 1rem;">📭</div>
        <h3 class="mb-3">No Results Found</h3>
        <p class="text-muted mb-4">Your results haven't been uploaded yet.</p>
        <div class="alert alert-warning d-inline-block">
          <strong>⚠️ What to do?</strong>
          <p class="mb-0 mt-2">Please contact your administrator for more information about your results.</p>
        </div>
      </div>
    <?php else: ?>
      <!-- Result Header -->
      <div class="result-header" style="position: relative; z-index: 1;">
        <div class="text-center">
          <h2 class="fw-bold mb-1">🎓 Academic Marksheet</h2>
          <p class="mb-0 opacity-75">Official Result Card</p>
        </div>
      </div>

      <!-- Student Info -->
      <div class="p-4 bg-light border-bottom">
        <div class="row">
          <div class="col-md-6">
            <label class="text-muted small fw-bold">STUDENT NAME</label>
            <div class="h5 mb-0"><?php echo htmlspecialchars($_SESSION['student_name']); ?></div>
          </div>
          <div class="col-md-6 text-md-end">
            <label class="text-muted small fw-bold">USN</label>
            <div class="h5 mb-0"><?php echo htmlspecialchars($usn); ?></div>
          </div>
        </div>
      </div>

      <!-- Marks Table -->
      <div class="p-4">
        <h5 class="mb-4 fw-bold">📊 Subject-wise Marks</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th width="10%" class="text-center">#</th>
                <th width="60%">Subject Name</th>
                <th width="30%" class="text-center">Marks Obtained</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach($rows as $r): ?>
              <tr class="mark-row">
                <td class="text-center">
                  <span class="badge bg-light text-dark"><?php echo $i++; ?></span>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($r['subject']); ?></strong>
                </td>
                <td class="text-center">
                  <?php 
                    $marks = (int)$r['marks'];
                    $color = $marks >= 75 ? 'success' : ($marks >= 50 ? 'warning' : 'danger');
                  ?>
                  <span class="badge bg-<?php echo $color; ?> px-3 py-2">
                    <?php echo $marks; ?> / 100
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Summary Section -->
      <div class="p-4 bg-light">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="row">
              <div class="col-6 mb-3">
                <label class="text-muted small fw-bold">TOTAL MARKS</label>
                <div class="h4 mb-0 fw-bold text-primary">
                  <?php echo $total; ?> / <?php echo $maxTotal; ?>
                </div>
              </div>
              <div class="col-6 mb-3">
                <label class="text-muted small fw-bold">PERCENTAGE</label>
                <div class="h4 mb-0 fw-bold text-success">
                  <?php echo $percentage; ?>%
                </div>
              </div>
              <div class="col-12">
                <label class="text-muted small fw-bold">RESULT STATUS</label>
                <div>
                  <?php if ($percentage >= 50): ?>
                    <span class="badge bg-success px-3 py-2">
                      ✅ PASSED
                    </span>
                  <?php else: ?>
                    <span class="badge bg-danger px-3 py-2">
                      ❌ FAILED
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <label class="text-muted small fw-bold d-block mb-2">GRADE</label>
            <div class="grade-badge">
              <?php echo $grade; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 text-center border-top">
        <p class="text-muted mb-1">
          <small><strong>Date Generated:</strong> <?php echo date('d F Y, h:i A'); ?></small>
        </p>
        <p class="text-muted mb-0">
          <small>This is a computer-generated document. No signature is required.</small>
        </p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Action Buttons -->
  <div class="mt-4 text-center no-print fade-in">
    <?php if (count($rows) > 0): ?>
      <button class="btn btn-primary btn-lg me-2 px-5" onclick="printResult()">
        🖨️ Print Marksheet
      </button>
    <?php endif; ?>
    <a href="student_dashboard.php" class="btn btn-secondary btn-lg px-5">
      ← Back to Dashboard
    </a>
  </div>
</div>

<script src="assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

// Page for viewing student results.

