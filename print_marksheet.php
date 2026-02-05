<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_logged_in'])) {
    header("Location: student_login.php");
    exit;
}

$session_usn = $_SESSION['student_usn'];
$usn = strtoupper(trim($_GET['usn'] ?? ''));

if ($usn === '' || $usn !== $session_usn) {
    die("❌ Unauthorized access.");
}

// Fetch student details
$stmtStudent = $conn->prepare("SELECT name, email, father_name, class FROM students WHERE usn = ?");
$stmtStudent->bind_param("s", $usn);
$stmtStudent->execute();
$studentData = $stmtStudent->get_result()->fetch_assoc();
$stmtStudent->close();

// Fetch results
$stmt = $conn->prepare("SELECT subject, marks, max_marks FROM results WHERE usn = ? ORDER BY subject ASC");
$stmt->bind_param("s", $usn);
$stmt->execute();
$res = $stmt->get_result();

$rows = $res->fetch_all(MYSQLI_ASSOC);
$total = 0;
$maxTotal = 0;

foreach ($rows as $r) {
    $total += (int)$r['marks'];
    $maxTotal += (int)($r['max_marks'] ?? 100);
}

$percentage = $maxTotal > 0 ? round(($total / $maxTotal) * 100, 2) : 0;

// Determine grade
if ($percentage >= 90) { $grade = "A1"; $result = "Outstanding"; }
elseif ($percentage >= 80) { $grade = "A2"; $result = "Excellent"; }
elseif ($percentage >= 70) { $grade = "B1"; $result = "Very Good"; }
elseif ($percentage >= 60) { $grade = "B2"; $result = "Good"; }
elseif ($percentage >= 50) { $grade = "C1"; $result = "Average"; }
elseif ($percentage >= 40) { $grade = "C2"; $result = "Below Average"; }
else { $grade = "D"; $result = "Needs Improvement"; }

$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Marksheet - <?php echo htmlspecialchars($usn); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f5e6d3 0%, #d4af37 100%);
      padding: 20px;
    }
    
    #printArea {
      background: white;
      max-width: 900px;
      margin: 0 auto;
      box-shadow: 0 0 30px rgba(0,0,0,0.1);
      padding: 0;
    }
    
    .marksheet-header {
      border: 5px solid #1e3a8a;
      padding: 20px;
      text-align: center;
      background: linear-gradient(to bottom, #f0f9ff 0%, #ffffff 100%);
      border-radius: 10px 10px 0 0;
    }
    
    .school-logo {
      width: 100px;
      height: 100px;
      background: #d4af37;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      margin-bottom: 10px;
      border: 3px solid #1e3a8a;
    }
    
    .school-name {
      color: #1e3a8a;
      font-size: 2.5rem;
      font-weight: 800;
      margin: 10px 0;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    .affiliation {
      color: #047857;
      font-weight: 700;
      font-size: 1.1rem;
      margin: 5px 0;
    }
    
    .marksheet-title {
      color: #dc2626;
      font-size: 1.5rem;
      font-weight: 700;
      margin-top: 10px;
      text-transform: uppercase;
    }
    
    .student-info-table {
      width: 100%;
      margin: 20px 0;
    }
    
    .student-info-table td {
      padding: 10px;
      border: 2px solid #1e3a8a;
    }
    
    .info-label {
      font-weight: 700;
      background: #f3f4f6;
      width: 30%;
    }
    
    .marks-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    
    .marks-table th {
      background: #1e3a8a;
      color: white;
      padding: 12px;
      text-align: center;
      border: 2px solid #1e3a8a;
      font-weight: 700;
    }
    
    .marks-table td {
      padding: 10px;
      border: 2px solid #cbd5e1;
      text-align: center;
    }
    
    .marks-table tbody tr:nth-child(odd) {
      background: #f8fafc;
    }
    
    .total-row {
      background: #dbeafe !important;
      font-weight: 700;
      font-size: 1.1rem;
    }
    
    .result-summary {
      margin: 20px 0;
      padding: 15px;
      background: #fef3c7;
      border: 3px solid #d4af37;
      border-radius: 10px;
    }
    
    .result-summary table {
      width: 100%;
    }
    
    .result-summary td {
      padding: 8px;
      font-size: 1.1rem;
      font-weight: 600;
    }
    
    .signature-section {
      margin-top: 40px;
      padding: 20px 0;
      border-top: 2px solid #cbd5e1;
    }
    
    .signature-box {
      text-align: right;
      padding-right: 50px;
    }
    
    .signature-line {
      border-top: 2px solid #000;
      width: 200px;
      margin: 10px 0 5px auto;
    }
    
    .print-date {
      font-size: 0.9rem;
      color: #666;
      margin: 20px 0;
      padding: 10px;
      background: #f3f4f6;
      border-radius: 5px;
    }
    
    .disclaimer {
      background: #fef2f2;
      border-left: 4px solid #dc2626;
      padding: 15px;
      margin: 20px 0;
      font-size: 0.85rem;
      color: #666;
    }
    
    @media print {
      body {
        background: white;
        padding: 0;
      }
      
      #printArea {
        box-shadow: none;
        max-width: 100%;
      }
      
      .no-print {
        display: none !important;
      }
      
      .marksheet-header {
        border-radius: 0;
      }
    }
  </style>
</head>
<body>

<!-- Action Buttons (No Print) -->
<div class="text-center mb-3 no-print">
  <?php if (count($rows) > 0): ?>
    <button class="btn btn-lg me-2" onclick="window.print()" style="background: #d4af37; color: #000; font-weight: 600; padding: 12px 40px;">
      🖨️ Print Marksheet
    </button>
  <?php endif; ?>
  <a href="student_dashboard.php" class="btn btn-secondary btn-lg" style="padding: 12px 40px;">
    ← Back to Dashboard
  </a>
</div>

<div id="printArea">
  <?php if (count($rows) === 0): ?>
    <!-- No Records -->
    <div class="marksheet-header">
      <div class="school-logo">🎓</div>
      <h1 class="school-name">Your School Name Here</h1>
      <div class="affiliation">Affiliated to CBSE - Affiliation Code: CBSE-XXX-XXXX</div>
    </div>
    <div style="padding: 50px; text-align: center;">
      <div style="font-size: 5rem; opacity: 0.2;">🔭</div>
      <h3>No Results Found</h3>
      <p class="text-muted">Your marksheet hasn't been prepared yet. Please contact your administrator.</p>
    </div>
  <?php else: ?>
    <!-- School Header -->
    <div class="marksheet-header">
      <div class="school-logo">🎓</div>
      <h1 class="school-name">RVCE</h1>
      <div class="affiliation"></div>
      <div class="marksheet-title">MARK SHEET </div>
    </div>

    <div style="padding: 30px;">
      <!-- Student Information -->
      <table class="student-info-table">
        <tr>
          <td class="info-label">Student Name:</td>
          <td><strong><?php echo htmlspecialchars($studentData['name'] ?? 'N/A'); ?></strong></td>
          <td class="info-label">Admission No:</td>
          <td><strong><?php echo htmlspecialchars($usn); ?></strong></td>
        </tr>
        <tr>
          <td class="info-label">Father's Name:</td>
          <td><strong><?php echo htmlspecialchars($studentData['father_name'] ?? 'N/A'); ?></strong></td>
          <td class="info-label">Class:</td>
          <td><strong><?php echo htmlspecialchars($studentData['class'] ?? 'N/A'); ?></strong></td>
        </tr>
      </table>

      <!-- Marks Table -->
      <table class="marks-table">
        <thead>
          <tr>
            <th rowspan="2" width="50">S.No.</th>
            <th rowspan="2">SUBJECTS</th>
            <th colspan="2">Marks</th>
          </tr>
          <tr>
            <th width="120">FULL MARK</th>
            <th width="120">SECURED MARK</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach($rows as $r): ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td style="text-align: left; padding-left: 20px;"><strong><?php echo strtoupper(htmlspecialchars($r['subject'])); ?></strong></td>
            <td><?php echo (int)($r['max_marks'] ?? 100); ?></td>
            <td><strong><?php echo htmlspecialchars($r['marks']); ?></strong></td>
          </tr>
          <?php endforeach; ?>
          
          <!-- Total Row -->
          <tr class="total-row">
            <td colspan="2" style="text-align: right; padding-right: 20px;">TOTAL</td>
            <td><?php echo $maxTotal; ?></td>
            <td><?php echo $total; ?></td>
          </tr>
        </tbody>
      </table>

      <!-- Result Summary -->
      <div class="result-summary">
        <table>
          <tr>
            <td style="width: 33%;"><strong>Percentage:</strong> <?php echo $percentage; ?>%</td>
            <td style="width: 33%;"><strong>Grade:</strong> <?php echo $grade; ?></td>
            <td style="width: 34%;"><strong>Result:</strong> <?php echo $result; ?></td>
          </tr>
        </table>
      </div>

      <!-- Signature Section -->
      <div class="signature-section">
        <div class="signature-box">
          <div class="signature-line"></div>
          <strong>HEAD OF DEPARTMENT(HOD)</strong>
        </div>
      </div>

      <!-- Print Date -->
      <div class="print-date">
        <strong>Print Date:</strong> <?php echo date('F d, Y, h:i A'); ?>
      </div>

      <!-- Disclaimer -->
      <div class="disclaimer">
        <strong>Disclaimer:</strong> Neither webmaster nor Result Hosting is responsible for any inadvertent error that may creep in the results being published on NET. The results published on net are immediate information for Students. This cannot be treated as original mark sheet. Original mark sheets will be issued by the School office separately.
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Back Button (No Print) -->
<div class="text-center mt-4 mb-5 no-print">
  <a href="student_dashboard.php" class="btn btn-secondary btn-lg" style="padding: 12px 40px;">
    ← Back to Dashboard
  </a>
</div>

</body>
</html>