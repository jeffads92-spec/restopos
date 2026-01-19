<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔌 Database Connection Test</h1>";
echo "<hr>";

// Get Railway variables
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';

echo "<h2>📊 Configuration:</h2>";
echo "<pre>";
echo "MYSQLHOST: " . ($host) . "\n";
echo "MYSQLPORT: " . ($port) . "\n";
echo "MYSQLDATABASE: " . ($dbname) . "\n";
echo "MYSQLUSER: " . ($user) . "\n";
echo "MYSQLPASSWORD: " . (empty($pass) ? 'NOT SET' : '***SET***') . "\n";
echo "</pre>";

echo "<h2>🔄 Connection Attempts:</h2>";

// Attempt 1: Using internal hostname
echo "<h3>Attempt 1: Internal hostname ($host:$port)</h3>";
try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724;'>";
    echo "✅ <strong>SUCCESS!</strong> Connected to database<br>";
    echo "Server version: " . $conn->server_info . "<br>";
    echo "Connection ID: " . $conn->thread_id;
    echo "</div>";
    
    // Try to create a test table
    echo "<h3>🧪 Testing Database Operations:</h3>";
    
    if ($conn->query("CREATE TABLE IF NOT EXISTS connection_test (id INT AUTO_INCREMENT PRIMARY KEY, test_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP)")) {
        echo "<p style='color: green;'>✓ Table creation: SUCCESS</p>";
        
        if ($conn->query("INSERT INTO connection_test VALUES ()")) {
            echo "<p style='color: green;'>✓ Inser
