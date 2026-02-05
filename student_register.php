<?php
session_start();
require_once 'config.php';

$err = $info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usn = strtoupper(trim($_POST['usn'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    
    if ($usn === '' || $name === '' || $email === '' || $pass === '') {
        $err = "USN, Name, Email and Password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address.";
    } elseif (strlen($pass) < 6) {
        $err = "Password must be at least 6 characters long.";
    } elseif ($pass !== $pass2) {
        $err = "Passwords do not match.";
    } else {
        // Check if USN already exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE usn = ? OR email = ?");
        $stmt->bind_param("ss", $usn, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $err = "USN or Email already registered. Please login instead.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO students (usn, name, email, father_name, class, password) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssss", $usn, $name, $email, $father_name, $class, $hash);
            
            if ($ins->execute()) {
                $info = "Registration successful! You can now login with your Email and USN.";
            } else {
                $err = "Failed to register: " . $conn->error;
            }
            $ins->close();
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
  <title>Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      min-height: 100vh;
    }
    .register-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(212, 175, 55, 0.3);
      animation: fadeInUp 0.8s ease-out;
    }
    .register-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="register-card p-5">
        <div class="register-icon">📝</div>
        <h4 class="text-center mb-4 fw-bold">Student Registration</h4>
        
        <?php if($err): ?>
          <div class="alert alert-danger border-0">
            <strong>❌ Error!</strong> <?php echo htmlspecialchars($err); ?>
          </div>
        <?php endif; ?>
        
        <?php if($info): ?>
          <div class="alert alert-success border-0">
            <strong>✅ Success!</strong> <?php echo htmlspecialchars($info); ?>
            <div class="mt-2">
              <a href="student_login.php" class="btn btn-sm btn-success">Login Now →</a>
            </div>
          </div>
        <?php endif; ?>
        
        <form method="post" novalidate id="registerForm">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <strong>USN (University Seat Number)</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="usn" class="form-control form-control-lg" placeholder="e.g., 1MS21CS001" required>
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <strong>Email Address</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="email" type="email" class="form-control form-control-lg" placeholder="your@email.com" required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <strong>Full Name</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="name" class="form-control form-control-lg" placeholder="e.g., John Doe" required>
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <strong>Father's Name</strong>
              </label>
              <input name="father_name" class="form-control form-control-lg" placeholder="Father's name">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">
              <strong>Class</strong>
            </label>
            <input name="class" class="form-control form-control-lg" placeholder="e.g., 6">
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <strong>Password</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="password" type="password" class="form-control form-control-lg" minlength="6" placeholder="Min 6 characters" required id="password">
              <div class="password-strength">
                <div class="password-strength-bar" id="strengthBar"></div>
              </div>
            </div>
            
            <div class="col-md-6 mb-4">
              <label class="form-label">
                <strong>Confirm Password</strong>
                <span class="text-danger">*</span>
              </label>
              <input name="password2" type="password" class="form-control form-control-lg" minlength="6" placeholder="Re-enter password" required id="password2">
              <small class="text-muted" id="matchMessage"></small>
            </div>
          </div>
          
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); color: #000;">
              📝 Register Now
            </button>
          </div>
        </form>
        
        <hr>
        
        <div class="text-center">
          <p class="mb-2">
            <a href="student_login.php" class="text-decoration-none fw-bold">
              🔐 Already registered? Login here
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
<script>
document.getElementById('password').addEventListener('input', function() {
  const password = this.value;
  const strengthBar = document.getElementById('strengthBar');
  let strength = 0;
  
  if (password.length >= 6) strength += 25;
  if (password.length >= 10) strength += 25;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
  if (/[0-9]/.test(password)) strength += 25;
  
  strengthBar.style.width = strength + '%';
  
  if (strength <= 25) strengthBar.style.background = 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)';
  else if (strength <= 50) strengthBar.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
  else if (strength <= 75) strengthBar.style.background = 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)';
  else strengthBar.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
});

document.getElementById('password2').addEventListener('input', function() {
  const password = document.getElementById('password').value;
  const password2 = this.value;
  const message = document.getElementById('matchMessage');
  
  if (password2.length > 0) {
    if (password === password2) {
      message.innerHTML = '<span style="color: #11998e;">✓ Passwords match</span>';
    } else {
      message.innerHTML = '<span style="color: #eb3349;">✗ Passwords do not match</span>';
    }
  } else {
    message.innerHTML = '';
  }
});
</script>
</body>
</html>