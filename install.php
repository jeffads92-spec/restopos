<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';

echo "<h1>Database Installer</h1>";
echo "<p>Connecting to: $host:$port / $dbname</p>";

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✓ Connected!</p>";
    
    // Create users table
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        role ENUM('admin','kasir') DEFAULT 'kasir',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✓ Created users table</p>";
    
    // Create categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✓ Created categories table</p>";
    
    // Create products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        product_name VARCHAR(200) NOT NULL,
        description TEXT,
        cost_price DECIMAL(12,2) DEFAULT 0,
        selling_price DECIMAL(12,2) NOT NULL,
        stock_quantity INT DEFAULT 0,
        unit VARCHAR(50) DEFAULT 'porsi',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
    )");
    echo "<p>✓ Created products table</p>";
    
    // Insert admin user
    $conn->query("INSERT IGNORE INTO users (username, password, full_name, email, role) VALUES 
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@stasiunkerang.com', 'admin')");
    echo "<p>✓ Inserted admin user</p>";
    
    echo "<h2>✅ Installation Complete!</h2>";
    echo "<p><strong>Login:</strong> admin / admin123</p>";
    echo "<p><a href='index.php'>Go to Application</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}
?>
