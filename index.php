<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Result System - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      min-height: 100vh;
      position: relative;
      overflow: hidden;
    }
    
    body::before, body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: 0.1;
    }
    
    body::before {
      width: 500px;
      height: 500px;
      background: white;
      top: -250px;
      right: -250px;
      animation: float 20s ease-in-out infinite;
    }
    
    body::after {
      width: 300px;
      height: 300px;
      background: white;
      bottom: -150px;
      left: -150px;
      animation: float 15s ease-in-out infinite reverse;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px) translateX(0px); }
      50% { transform: translateY(50px) translateX(50px); }
    }
    
    .main-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(212, 175, 55, 0.3);
      position: relative;
      z-index: 1;
      animation: fadeInUp 0.8s ease-out;
      border: 3px solid rgba(212, 175, 55, 0.2);
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .logo-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      margin: 0 auto 1rem;
      box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
      border: 3px solid #d4af37;
    }
    
    .role-btn {
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
      transition: all 0.3s ease;
    }
    
    .role-btn .icon {
      font-size: 1.5rem;
      margin-right: 0.5rem;
    }
    
    .role-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }
    
    .golden-btn {
      background: linear-gradient(135deg, #d4af37 0%, #f5e6d3 100%);
      color: #000;
      font-weight: 600;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="main-card p-5 shadow-lg" style="width:480px; max-width:90%;">
    <div class="text-center mb-4">
      <div class="logo-icon">
        🎓
      </div>
      <h2 class="fw-bold mb-2" style="color: #8B7355;">Student Result System</h2>
      <p class="text-muted mb-0">Secure & Efficient Result Management</p>
    </div>
    
    <div class="d-grid gap-3 mt-4">
      <a href="student_login.php" class="btn btn-lg role-btn golden-btn">
        <span class="icon">👨‍🎓</span>
        <span>Student Login</span>
      </a>
      <a href="student_register.php" class="btn btn-lg role-btn" style="background: linear-gradient(135deg, #8B7355 0%, #5D4E37 100%); color: white;">
        <span class="icon">📝</span>
        <span>Student Register</span>
      </a>
      <a href="admin_login.php" class="btn btn-secondary btn-lg role-btn">
        <span class="icon">👨‍🏫</span>
        <span>Admin Login</span>
      </a>
    </div>
    
    <div class="text-center mt-4">
      <small class="text-muted">
        © 2024 Student Result System. All rights reserved.
      </small>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>