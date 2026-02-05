<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $err = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['LAST_ACTIVITY'] = time();
                header("Location: admin_dashboard.php");
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
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #232526 0%, #414345 100%);
      min-height: 100vh;
      position: relative;
    }
    body::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></svg>');
      opacity: 0.5;
    }
    .admin-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      z-index: 1;
    }
    .admin-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #232526 0%, #414345 100%);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    .secure-badge {
      display: inline-block;
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="admin-card p-5">
        <div class="text-center">
          <div class="admin-icon">👨‍🏫</div>
          <span class="secure-badge">🔒 Secure Admin Access</span>
        </div>
        
        <h4 class="text-center mb-4 fw-bold">Administrator Login</h4>
        
        <?php if($err): ?>
          <div class="alert alert-danger border-0">
            <strong>❌ Access Denied!</strong><br>
            <?php echo htmlspecialchars($err); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" novalidate>
          <div class="mb-4">
            <label class="form-label">
              <strong>Username</strong>
              <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
              <span class="input-group-text">👤</span>
              <input name="username" type="text" class="form-control" placeholder="Enter admin username" required autocomplete="username">
            </div>
          </div>
          
          <div class="mb-4">
            <label class="form-label">
              <strong>Password</strong>
              <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
              <span class="input-group-text">🔑</span>
              <input name="password" type="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
            </div>
          </div>
          
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-secondary btn-lg">
              🔐 Secure Login
            </button>
          </div>
        </form>
        
        <hr>
        
        <div class="text-center">
          <p class="mb-3">
            <a href="admin_forgot_password.php" class="text-decoration-none fw-bold">
              🔑 Forgot Password?
            </a>
          </p>
          <div class="alert alert-info border-0 mb-3">
            <small>
              <strong>ℹ️ Default Credentials</strong><br>
              Username: <code>admin</code> | Password: <code>admin123</code>
            </small>
          </div>
          <p class="mb-0">
            <a href="index.php" class="text-muted">← Back to Home</a>
          </p>
        </div>
      </div>
      
      <div class="text-center mt-3">
        <small class="text-white-50">
          🔒 This is a secure admin area. All activities are logged.
        </small>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>