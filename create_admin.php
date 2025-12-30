<?php
// create_admin.php - Run once to setup database, then DELETE this file for security
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_result_db";

try {
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    $conn->select_db($dbname);
    
    // Create tables
    $sqlFile = __DIR__ . '/create_tables.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if ($sql) {
            $conn->multi_query($sql);
            while ($conn->next_result()) {;} // Flush multi_queries
            
            if ($conn->error) {
                throw new Exception("Error creating tables: " . $conn->error);
            }
        }
    } else {
        echo "Warning: create_tables.sql file not found.<br>";
    }
    
    // Create default admin if not exists
    $adminUser = 'admin';
    $adminPass = 'admin123'; // CHANGE AFTER FIRST LOGIN
    
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $adminUser);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $ins = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $ins->bind_param("ss", $adminUser, $hash);
        
        if ($ins->execute()) {
            echo "<div style='padding:20px; background:#d4edda; border:1px solid #c3e6cb; border-radius:5px; margin:20px;'>";
            echo "<h3>✅ Setup Complete!</h3>";
            echo "<p><strong>Admin Credentials:</strong></p>";
            echo "<ul>";
            echo "<li>Username: <strong>admin</strong></li>";
            echo "<li>Password: <strong>admin123</strong></li>";
            echo "</ul>";
            echo "<p style='color:#721c24; background:#f8d7da; padding:10px; border-radius:5px;'>";
            echo "⚠️ <strong>IMPORTANT:</strong> Please change the password after first login and DELETE this file immediately for security!";
            echo "</p>";
            echo "</div>";
        } else {
            throw new Exception("Failed to create admin user: " . $ins->error);
        }
    } else {
        echo "<div style='padding:20px; background:#fff3cd; border:1px solid #ffeeba; border-radius:5px; margin:20px;'>";
        echo "<h3>ℹ️ Setup Already Complete</h3>";
        echo "<p>Admin user already exists.</p>";
        echo "<p style='color:#721c24;'><strong>DELETE THIS FILE NOW for security!</strong></p>";
        echo "</div>";
    }
    
    echo "<div style='padding:20px; margin:20px;'>";
    echo "<p><a href='index.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Go to Homepage</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='padding:20px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
