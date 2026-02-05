<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_logged_in'])) {
    header("Location: student_login.php");
    exit;
}

$usn = $_SESSION['student_usn'];
$name = $_SESSION['student_name'] ?? '';
$email = $_SESSION['student_email'] ?? '';

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
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      min-height: 100vh;
    }
    .welcome-card {
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      color: #000;
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
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .stat-card {
      border-left: 4px solid #d4af37;
      transition: all 0.3s ease;
      background: white;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #8B7355 0%, #5D4E37 100%);">
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
          <h2 class="mb-2 fw-bold">Welcome, <?php echo htmlspecialchars($name); ?>! 🎉</h2>
          <p class="mb-0" style="opacity: 0.8;">USN: <?php echo htmlspecialchars($usn); ?> | Email: <?php echo htmlspecialchars($email); ?></p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <?php if ($stats['subjects'] > 0): ?>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="text-muted mb-2">Total Subjects</h6>
              <h2 class="mb-0 fw-bold" style="color: #d4af37;"><?php echo $stats['subjects']; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card border-0 shadow-sm" style="border-left-color: #8B7355 !important;">
            <div class="card-body">
              <h6 class="text-muted mb-2">Total Marks</h6>
              <h2 class="mb-0 fw-bold" style="color: #8B7355;"><?php echo $stats['total']; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card border-0 shadow-sm" style="border-left-color: #5D4E37 !important;">
            <div class="card-body">
              <h6 class="text-muted mb-2">Average</h6>
              <h2 class="mb-0 fw-bold" style="color: #5D4E37;"><?php echo round($stats['avg'], 1); ?>%</h2>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Generate Result Card -->
      <div class="card shadow-sm mb-4 fade-in border-0">
        <div class="card-body text-center p-5">
          <div class="mb-4" style="font-size: 4rem; opacity: 0.3;">📄</div>
          <h3 class="mb-3 fw-bold">Generate Your Official Marksheet</h3>
          <p class="text-muted mb-4">View, download and print your complete academic result</p>
          <form action="student_result.php" method="get" class="d-inline">
            <input type="hidden" name="usn" value="<?php echo htmlspecialchars($usn); ?>">
            <button class="btn btn-lg px-5" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); color: #000; font-weight: 600;">
              📊 Generate My Marksheet
            </button>
          </form>
        </div>
      </div>

      <!-- Information Card -->
      <div class="card shadow-sm fade-in border-0">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #8B7355 0%, #5D4E37 100%);">
          <strong>ℹ️ Student Information</strong>
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
            <div class="col-md-12 mb-3">
              <label class="text-muted small">Email Address</label>
              <div class="fw-bold"><?php echo htmlspecialchars($email); ?></div>
            </div>
          </div>
          
          <hr>
          
          <div class="alert border-0" style="background: #fef3c7; border-left: 4px solid #d4af37 !important;">
            <div class="d-flex">
              <div class="me-2" style="font-size: 1.5rem;">📌</div>
              <div>
                <strong>Important Note:</strong>
                <small class="d-block mt-1">
                  If your results show "No records found", your marks haven't been uploaded yet. 
                  You will receive an email notification once your results are available.
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