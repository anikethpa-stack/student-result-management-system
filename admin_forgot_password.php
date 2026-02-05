<?php
session_start();
require_once 'config.php';

// If already logged in, redirect
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$step = 1;
$err = '';
$info = '';
$verified_admin_id = null;

// Step 1: Verify Username
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_username'])) {
    $username = trim($_POST['username']);
    
    if ($username === '') {
        $err = "Please enter your username.";
    } else {
        $stmt = $conn->prepare("SELECT id, security_question FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['forgot_admin_id'] = $row['id'];
            $_SESSION['security_question'] = $row['security_question'] ?? 'What is your favorite color?';
            $step = 2;
        } else {
            $err = "Username not found.";
        }
        $stmt->close();
    }
}

// Step 2: Verify Security Answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_answer'])) {
    $security_answer = trim($_POST['security_answer']);
    $admin_id = $_SESSION['forgot_admin_id'] ?? 0;
    
    if ($security_answer === '') {
        $err = "Please enter your security answer.";
        $step = 2;
    } else {
        $stmt = $conn->prepare("SELECT id, security_answer FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // For initial setup, if security_answer is null, accept any answer
            if ($row['security_answer'] === null || password_verify($security_answer, $row['security_answer'])) {
                $_SESSION['verified_admin'] = true;
                $step = 3;
            } else {
                $err = "Incorrect security answer.";
                $step = 2;
            }
        }
        $stmt->close();
    }
}

// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['verified_admin']) || !$_SESSION['verified_admin']) {
        header("Location: admin_forgot_password.php");
        exit;
    }
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_id = $_SESSION['forgot_admin_id'];
    
    if ($new_password === '' || $confirm_password === '') {
        $err = "Please fill all fields.";
        $step = 3;
    } elseif (strlen($new_password) < 6) {
        $err = "Password must be at least 6 characters.";
        $step = 3;
    } elseif ($new_password !== $confirm_password) {
        $err = "Passwords do not match.";
        $step = 3;
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $admin_id);
        
        if ($stmt->execute()) {
            // Clear session
            unset($_SESSION['forgot_admin_id']);
            unset($_SESSION['security_question']);
            unset($_SESSION['verified_admin']);
            
            $info = "Password reset successful! You can now login.";
            $step = 4; // Success step
        } else {
            $err = "Failed to reset password.";
            $step = 3;
        }
        $stmt->close();
    }
}

// Check which step to show
if (isset($_SESSION['verified_admin']) && $_SESSION['verified_admin']) {
    $step = 3;
} elseif (isset($_SESSION['forgot_admin_id'])) {
    $step = 2;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #232526 0%, #414345 100%);
      min-height: 100vh;
    }
    .forgot-card {
      background: rgba(255, 255, 255, 0.98);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      animation: fadeInUp 0.8s ease-out;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .step-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
    }
    .step {
      flex: 1;
      text-align: center;
      padding: 10px;
      background: #e0e0e0;
      margin: 0 5px;
      border-radius: 8px;
      font-weight: 600;
      position: relative;
    }
    .step.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
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
          <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #232526 0%, #414345 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem;">🔐</div>
          <h4 class="fw-bold">Admin Password Recovery</h4>
          <p class="text-muted">Follow the steps to reset your password</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
          <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
            1. Username
          </div>
          <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
            2. Verify
          </div>
          <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
            3. New Password
          </div>
        </div>

        <?php if($err): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>
        
        <?php if($info): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
          <!-- Step 1: Enter Username -->
          <form method="post">
            <div class="mb-4">
              <label class="form-label"><strong>Enter Your Username</strong></label>
              <input type="text" name="username" class="form-control form-control-lg" placeholder="Admin username" required autofocus>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" name="verify_username" class="btn btn-primary btn-lg">Continue</button>
              <a href="admin_login.php" class="btn btn-secondary">Back to Login</a>
            </div>
          </form>

        <?php elseif ($step === 2): ?>
          <!-- Step 2: Security Question -->
          <form method="post">
            <div class="alert alert-info">
              <strong>Security Question:</strong><br>
              <?php echo htmlspecialchars($_SESSION['security_question'] ?? 'What is your favorite color?'); ?>
            </div>
            <div class="mb-4">
              <label class="form-label"><strong>Your Answer</strong></label>
              <input type="text" name="security_answer" class="form-control form-control-lg" placeholder="Enter your answer" required autofocus>
              <small class="text-muted">This answer was set during account creation (default: blue)</small>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" name="verify_answer" class="btn btn-primary btn-lg">Verify</button>
              <a href="admin_forgot_password.php" class="btn btn-secondary" onclick="<?php unset($_SESSION['forgot_admin_id']); ?>">Start Over</a>
            </div>
          </form>

        <?php elseif ($step === 3): ?>
          <!-- Step 3: Reset Password -->
          <form method="post">
            <div class="alert alert-success">
              <strong>✓ Identity Verified!</strong> You can now reset your password.
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

        <?php elseif ($step === 4): ?>
          <!-- Step 4: Success -->
          <div class="text-center py-4">
            <div style="font-size: 5rem; color: #28a745;">✓</div>
            <h4 class="mb-3">Password Reset Successful!</h4>
            <p class="text-muted mb-4">Your password has been updated. You can now login with your new password.</p>
            <a href="admin_login.php" class="btn btn-primary btn-lg px-5">Go to Login</a>
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