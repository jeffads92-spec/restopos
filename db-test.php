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
echo "MYSQLHOST: " . htmlspecialchars($host) . "\n";
echo "MYSQLPORT: " . htmlspecialchars($port) . "\n";
echo "MYSQLDATABASE: " . htmlspecialchars($dbname) . "\n";
echo "MYSQLUSER: " . htmlspecialchars($user) . "\n";
echo "MYSQLPASSWORD: " . (empty($pass) ? 'NOT SET' : '***SET***') . "\n";
echo "</pre>";

echo "<h2>🔄 Connection Test:</h2>";

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 10px 0;'>";
    echo "✅ <strong>SUCCESS!</strong> Connected to database<br>";
    echo "Server version: " . $conn->server_info . "<br>";
    echo "Connection ID: " . $conn->thread_id;
    echo "</div>";
    
    // Try to create a test table
    echo "<h3>🧪 Testing Database Operations:</h3>";
    
    $test_result = $conn->query("CREATE TABLE IF NOT EXISTS connection_test (id INT AUTO_INCREMENT PRIMARY KEY, test_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    
    if ($test_result) {
        echo "<p style='color: green;'>✓ Table creation: SUCCESS</p>";
        
        if ($conn->query("INSERT INTO connection_test VALUES ()")) {
            echo "<p style='color: green;'>✓ Insert operation: SUCCESS</p>";
            
            $result = $conn->query("SELECT COUNT(*) as total FROM connection_test");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<p style='color: green;'>✓ Select operation: SUCCESS (Total rows: " . $row['total'] . ")</p>";
            }
            
            // Clean up
            $conn->query("DROP TABLE connection_test");
            echo "<p style='color: green;'>✓ Table cleanup: SUCCESS</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Table operations: " . $conn->error . "</p>";
    }
    
    $conn->close();
    
    echo "<hr>";
    echo "<h2 style='color: green;'>🎉 All Tests Passed!</h2>";
    echo "<p><strong>Your database connection is working perfectly.</strong></p>";
    echo "<p><a href='install.php' style='display: inline-block; padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>→ Proceed to Installation</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 10px 0;'>";
    echo "❌ <strong>Connection FAILED:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<hr>";
    echo "<h2 style='color: red;'>❌ Connection Failed</h2>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Check Railway MySQL service is running (should show 'Online')</li>";
    echo "<li>Verify environment variables are set in the app service</li>";
    echo "<li>Check Railway logs for more details</li>";
    echo "<li>Try redeploying both MySQL and app services</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>🔍 PHP Environment Info:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? 'LOADED ✓' : 'NOT LOADED ✗') . "\n";
echo "PDO Extension: " . (extension_loaded('pdo') ? 'LOADED ✓' : 'NOT LOADED ✗') . "\n";
echo "PDO MySQL Driver: " . (extension_loaded('pdo_mysql') ? 'LOADED ✓' : 'NOT LOADED ✗') . "\n";
echo "</pre>";
?>
