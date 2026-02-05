<?php
session_start();
require_once 'config.php';

// If already logged in, redirect
if (isset($_SESSION['student_logged_in'])) {
    header("Location: student_dashboard.php");
    exit;
}

$step = 1;
$err = '';
$info = '';

// Step 1: Verify Email + USN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_identity'])) {
    $email = trim($_POST['email']);
    $usn = strtoupper(trim($_POST['usn']));
    
    if ($email === '' || $usn === '') {
        $err = "Please enter both Email and USN.";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM students WHERE email = ? AND usn = ?");
        $stmt->bind_param("ss", $email, $usn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['forgot_student_id'] = $row['id'];
            $_SESSION['forgot_student_name'] = $row['name'];
            $_SESSION['forgot_student_email'] = $email;
            $_SESSION['verified_student'] = true;
            $step = 2;
        } else {
            $err = "No student found with this Email and USN combination.";
        }
        $stmt->close();
    }
}

// Step 2: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['verified_student']) || !$_SESSION['verified_student']) {
        header("Location: student_forgot_password.php");
        exit;
    }
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = $_SESSION['forgot_student_id'];
    
    if ($new_password === '' || $confirm_password === '') {
        $err = "Please fill all fields.";
        $step = 2;
    } elseif (strlen($new_password) < 6) {
        $err = "Password must be at least 6 characters.";
        $step = 2;
    } elseif ($new_password !== $confirm_password) {
        $err = "Passwords do not match.";
        $step = 2;
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $student_id);
        
        if ($stmt->execute()) {
            // Clear session
            unset($_SESSION['forgot_student_id']);
            unset($_SESSION['forgot_student_name']);
            unset($_SESSION['forgot_student_email']);
            unset($_SESSION['verified_student']);
            
            $info = "Password reset successful! You can now login.";
            $step = 3; // Success step
        } else {
            $err = "Failed to reset password.";
            $step = 2;
        }
        $stmt->close();
    }
}

// Check which step to show
if (isset($_SESSION['verified_student']) && $_SESSION['verified_student']) {
    $step = 2;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student - Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      min-height: 100vh;
    }
    .forgot-card {
      background: rgba(255, 255, 255, 0.98);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(212, 175, 55, 0.3);
      animation: fadeInUp 0.8s ease-out;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .step-indicator {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .step {
      padding: 10px 20px;
      background: #e0e0e0;
      border-radius: 8px;
      font-weight: 600;
    }
    .step.active {
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      color: #000;
    }
    .step.completed {
      background: #28a745;
      color: white;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="forgot-card p-5">
        <div class="text-center mb-4">
          <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; border: 3px solid #d4af37;">🔐</div>
          <h4 class="fw-bold">Password Recovery</h4>
          <p class="text-muted">Reset your account password</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
          <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
            1. Verify Identity
          </div>
          <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
            2. New Password
          </div>
        </div>

        <?php if($err): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>
        
        <?php if($info): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
          <!-- Step 1: Verify Identity -->
          <form method="post">
            <div class="alert alert-info">
              <strong>ℹ️ Identity Verification</strong><br>
              Enter your registered Email and USN to verify your identity.
            </div>
            <div class="mb-3">
              <label class="form-label"><strong>Email Address</strong></label>
              <input type="email" name="email" class="form-control form-control-lg" placeholder="your@email.com" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label"><strong>USN</strong></label>
              <input type="text" name="usn" class="form-control form-control-lg" placeholder="Your USN" required>
              <small class="text-muted">Your University Seat Number</small>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" name="verify_identity" class="btn btn-lg" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); color: #000; font-weight: 600;">
                Verify & Continue
              </button>
              <a href="student_login.php" class="btn btn-secondary">Back to Login</a>
            </div>
          </form>

        <?php elseif ($step === 2): ?>
          <!-- Step 2: Reset Password -->
          <form method="post">
            <div class="alert alert-success">
              <strong>✓ Identity Verified!</strong><br>
              Welcome, <?php echo htmlspecialchars($_SESSION['forgot_student_name']); ?>. You can now reset your password.
            </div>
            <div class="mb-3">
              <label class="form-label"><strong>New Password</strong></label>
              <input type="password" name="new_password" class="form-control form-control-lg" minlength="6" required id="newPass">
              <div class="password-strength" style="height: 5px; background: #e0e0e0; border-radius: 5px; margin-top: 5px;">
                <div id="strengthBar" style="height: 100%; width: 0%; transition: all 0.3s;"></div>
              </div>
              <small class="text-muted">Minimum 6 characters</small>
            </div>
            <div class="mb-4">
              <label class="form-label"><strong>Confirm New Password</strong></label>
              <input type="password" name="confirm_password" class="form-control form-control-lg" minlength="6" required id="confirmPass">
              <small id="matchMsg"></small>
            </div>
            <div class="d-grid">
              <button type="submit" name="reset_password" class="btn btn-success btn-lg">Reset Password</button>
            </div>
          </form>

        <?php elseif ($step === 3): ?>
          <!-- Step 3: Success -->
          <div class="text-center py-4">
            <div style="font-size: 5rem; color: #28a745;">✓</div>
            <h4 class="mb-3">Password Reset Successful!</h4>
            <p class="text-muted mb-4">Your password has been updated. You can now login with your new password.</p>
            <a href="student_login.php" class="btn btn-lg px-5" style="background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%); color: #000; font-weight: 600;">
              Go to Login
            </a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('newPass')?.addEventListener('input', function() {
  const pass = this.value;
  const bar = document.getElementById('strengthBar');
  let strength = 0;
  if (pass.length >= 6) strength += 25;
  if (pass.length >= 10) strength += 25;
  if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) strength += 25;
  if (/[0-9]/.test(pass)) strength += 25;
  bar.style.width = strength + '%';
  if (strength <= 25) bar.style.background = '#dc3545';
  else if (strength <= 50) bar.style.background = '#ffc107';
  else if (strength <= 75) bar.style.background = '#17a2b8';
  else bar.style.background = '#28a745';
});

document.getElementById('confirmPass')?.addEventListener('input', function() {
  const newPass = document.getElementById('newPass').value;
  const confirmPass = this.value;
  const msg = document.getElementById('matchMsg');
  if (confirmPass.length > 0) {
    if (newPass === confirmPass) {
      msg.innerHTML = '<span style="color: #28a745;">✓ Passwords match</span>';
    } else {
      msg.innerHTML = '<span style="color: #dc3545;">✗ Passwords do not match</span>';
    }
  } else {
    msg.innerHTML = '';
  }
});
</script>
</body>
</html>