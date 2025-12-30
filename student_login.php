<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['student_logged_in'])) {
    header("Location: student_dashboard.php");
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usn = strtoupper(trim($_POST['usn'] ?? ''));
    $pass = $_POST['password'] ?? '';
    
    if ($usn === '' || $pass === '') {
        $err = "Please enter USN and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, name FROM students WHERE usn = ?");
        $stmt->bind_param("s", $usn);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['student_logged_in'] = true;
                $_SESSION['student_usn'] = $usn;
                $_SESSION['student_name'] = $row['name'];
                $_SESSION['LAST_ACTIVITY'] = time();
                header("Location: student_dashboard.php");
                exit;
            } else {
                $err = "Invalid credentials.";
            }
        } else {
            $err = "Invalid credentials.";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
    }
    .login-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
      animation: fadeInUp 0.8s ease-out;
    }
    .login-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="login-card p-5">
        <div class="login-icon">👨‍🎓</div>
        <h4 class="text-center mb-4 fw-bold">Student Login</h4>
        
        <?php if($err): ?>
          <div class="alert alert-danger border-0">
            <strong>❌ Error!</strong> <?php echo htmlspecialchars($err); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" novalidate>
          <div class="mb-4">
            <label class="form-label">
              <strong>USN</strong>
              <span class="text-danger">*</span>
            </label>
            <input name="usn" class="form-control form-control-lg" placeholder="Enter your USN" required autocomplete="username">
            <small class="text-muted">Your University Seat Number</small>
          </div>
          <div class="mb-4">
            <label class="form-label">
              <strong>Password</strong>
              <span class="text-danger">*</span>
            </label>
            <input name="password" type="password" class="form-control form-control-lg" placeholder="Enter password" required autocomplete="current-password">
          </div>
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg">
              🔐 Login
            </button>
          </div>
        </form>
        
        <hr>
        
        <div class="text-center">
          <p class="mb-2">
            <a href="student_register.php" class="text-decoration-none fw-bold">
              📝 New student? Register here
            </a>
          </p>
          <p class="mb-0">
            <a href="index.php" class="text-muted">← Back to Home</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

// Student login page.

