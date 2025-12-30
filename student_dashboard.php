<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_logged_in'])) {
    header("Location: student_login.php");
    exit;
}

$usn = $_SESSION['student_usn'];
$name = $_SESSION['student_name'] ?? '';

// Get student stats
$stmt = $conn->prepare("SELECT COUNT(*) as subjects, SUM(marks) as total, AVG(marks) as avg FROM results WHERE usn = ?");
$stmt->bind_param("s", $usn);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #e0e7ff 0%, #fde2e4 100%);
      min-height: 100vh;
    }
    .welcome-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 20px;
      position: relative;
      overflow: hidden;
    }
    .welcome-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .stat-card {
      border-left: 4px solid;
      transition: all 0.3s ease;
    }
    .stat-card:hover {
      transform: translateY(-5px);
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <span class="navbar-brand">
      <span style="font-size: 1.5rem;">👨‍🎓</span>
      Student Portal
    </span>
    <div class="d-flex align-items-center">
      <span class="text-white me-3">
        <small><?php echo htmlspecialchars($name); ?></small>
      </span>
      <a class="btn btn-outline-light btn-sm" href="student_logout.php">
        🚪 Logout
      </a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <!-- Welcome Card -->
      <div class="welcome-card p-5 mb-4 fade-in" style="position: relative;">
        <div style="position: relative; z-index: 1;">
          <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($name); ?>! 🎉</h2>
          <p class="mb-0 opacity-75">USN: <?php echo htmlspecialchars($usn); ?></p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <?php if ($stats['subjects'] > 0): ?>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card stat-card border-0" style="border-left-color: #667eea !important;">
            <div class="card-body">
              <h6 class="text-muted mb-2">Total Subjects</h6>
              <h2 class="mb-0 fw-bold"><?php echo $stats['subjects']; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card border-0" style="border-left-color: #11998e !important;">
            <div class="card-body">
              <h6 class="text-muted mb-2">Total Marks</h6>
              <h2 class="mb-0 fw-bold"><?php echo $stats['total']; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card border-0" style="border-left-color: #f093fb !important;">
            <div class="card-body">
              <h6 class="text-muted mb-2">Average</h6>
              <h2 class="mb-0 fw-bold"><?php echo round($stats['avg'], 1); ?>%</h2>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Generate Result Card -->
      <div class="card shadow-sm mb-4 fade-in">
        <div class="card-body text-center p-5">
          <div class="mb-4" style="font-size: 4rem; opacity: 0.2;">📄</div>
          <h3 class="mb-3 fw-bold">Generate Your Marksheet</h3>
          <p class="text-muted mb-4">View and download your complete academic result</p>
          <form action="student_result.php" method="get" class="d-inline">
            <input type="hidden" name="usn" value="<?php echo htmlspecialchars($usn); ?>">
            <button class="btn btn-success btn-lg px-5">
              📊 Generate My Result
            </button>
          </form>
        </div>
      </div>

      <!-- Information Card -->
      <div class="card shadow-sm fade-in">
        <div class="card-header bg-info text-white d-flex align-items-center">
          <span class="me-2" style="font-size: 1.3rem;">ℹ️</span>
          <strong>Quick Information</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">University Seat Number</label>
              <div class="fw-bold"><?php echo htmlspecialchars($usn); ?></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Full Name</label>
              <div class="fw-bold"><?php echo htmlspecialchars($name); ?></div>
            </div>
          </div>
          
          <hr>
          
          <div class="alert alert-warning border-0 mb-0">
            <div class="d-flex">
              <div class="me-2" style="font-size: 1.5rem;">📌</div>
              <div>
                <strong>Important Note:</strong>
                <small class="d-block mt-1">
                  If your results show "No records found", your marks haven't been uploaded yet. 
                  Please contact your administrator for more information.
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

// Student dashboard page.

