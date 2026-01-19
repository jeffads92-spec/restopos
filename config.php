<?php
/**
 * Smart Resto POS - Railway Database Configuration
 */

// Session configuration for Railway
ini_set('session.save_path', '/tmp');
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Detect environment
define('ENVIRONMENT', getenv('RAILWAY_ENVIRONMENT') ? 'production' : 'development');

// Database Configuration - Railway Environment Variables
if (ENVIRONMENT === 'production') {
    // TRY INTERNAL FIRST, FALLBACK TO PUBLIC
    define('DB_HOST', getenv('MYSQLHOST') ?: 'mysql.railway.internal');
    define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
    define('DB_USER', getenv('MYSQLUSER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
} else {
    // Local Development
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'smart_resto_pos');
}

// Application Configuration
define('APP_NAME', 'Stasiun Kerang POS');
define('APP_VERSION', '1.0.0');

// Base URL - Auto detect with Railway support
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
            ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = rtrim($base_path, '/');
define('BASE_URL', $protocol . '://' . $host . $base_path . '/');

// Upload Configuration - Use /tmp for Railway
if (ENVIRONMENT === 'production') {
    define('UPLOAD_PATH', '/tmp/uploads/products/');
} else {
    define('UPLOAD_PATH', __DIR__ . '/uploads/products/');
}
define('UPLOAD_URL', BASE_URL . 'uploads/products/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Tax Configuration
define('TAX_RATE', 10);
define('POINTS_PER_1000', 1);

// Currency
define('CURRENCY', 'Rp');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Database Connection with error handling
$conn = null;
$connection_attempts = 0;
$max_attempts = 3;

while ($connection_attempts < $max_attempts && $conn === null) {
    try {
        $connection_attempts++;
        
        // Log connection attempt
        if (ENVIRONMENT === 'production') {
            error_log("Database connection attempt $connection_attempts: " . DB_HOST . ":" . DB_PORT);
        }
        
        // Create connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        if (ENVIRONMENT === 'production') {
            error_log("✅ Database connected successfully!");
        }
        
        break; // Success, exit loop
        
    } catch (Exception $e) {
        error_log("❌ Connection attempt $connection_attempts failed: " . $e->getMessage());
        
        // If not last attempt, wait before retry
        if ($connection_attempts < $max_attempts) {
            sleep(1);
            $conn = null;
        } else {
            // All attempts failed
            error_log("All connection attempts failed");
            
            if (ENVIRONMENT === 'development') {
                die("Database error: " . $e->getMessage());
            } else {
                http_response_code(503);
                die("
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Service Unavailable</title>
                    <meta charset='utf-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <style>
                        body { 
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                            padding: 20px;
                        }
                        .error-container {
                            background: white;
                            padding: 40px;
                            border-radius: 20px;
                            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                            text-align: center;
                            max-width: 500px;
                            width: 100%;
                        }
                        h1 { color: #e74c3c; margin: 0 0 20px 0; }
                        p { color: #555; line-height: 1.6; margin: 10px 0; }
                        .icon { font-size: 64px; margin-bottom: 20px; }
                        .btn-retry {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 12px 30px;
                            background: #3498db;
                            color: white;
                            text-decoration: none;
                            border-radius: 8px;
                            font-weight: 600;
                        }
                    </style>
                </head>
                <body>
                    <div class='error-container'>
                        <div class='icon'>⚠️</div>
                        <h1>Service Unavailable</h1>
                        <p>We're having trouble connecting to the database.</p>
                        <p>Please try again in a few moments.</p>
                        <a href='javascript:location.reload()' class='btn-retry'>🔄 Retry Connection</a>
                    </div>
                </body>
                </html>
                ");
            }
        }
    }
}

// Helper Functions
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function checkAdmin() {
    checkLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getUserName() {
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
}

function getUserRole() {
    return isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Guest';
}

function getUserId() {
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
}

function escape($str) {
    global $conn;
    return $conn->real_escape_string(trim($str));
}

function formatRupiah($number, $prefix = null) {
    if ($prefix === null) {
        $prefix = CURRENCY . ' ';
    }
    return $prefix . number_format($number, 0, ',', '.');
}

function formatDate($date, $format = null) {
    if ($format === null) {
        $format = 'd/m/Y';
    }
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return '-';
    }
    return date($format, $timestamp);
}

function formatDateTime($datetime, $format = null) {
    if ($format === null) {
        $format = 'd/m/Y H:i';
    }
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '-';
    }
    return date($format, $timestamp);
}

function generateTransactionCode($db_conn = null) {
    global $conn;
    $database = $db_conn ?? $conn;
    
    $attempt = 0;
    $max_attempts = 20;
    
    do {
        $code = 'TRX' . date('Ymd') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $check = $database->query("SELECT transaction_id FROM transactions WHERE transaction_code = '$code'");
        if (!$check || $check->num_rows === 0) {
            return $code;
        }
        $attempt++;
    } while ($attempt < $max_attempts);
    
    return 'TRX' . date('YmdHis') . mt_rand(100, 999);
}

function generateMemberCode($db_conn = null) {
    global $conn;
    $database = $db_conn ?? $conn;
    
    $attempt = 0;
    $max_attempts = 20;
    
    do {
        $code = 'MBR' . date('Ymd') . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        $check = $database->query("SELECT member_id FROM members WHERE member_code = '$code'");
        if (!$check || $check->num_rows === 0) {
            return $code;
        }
        $attempt++;
    } while ($attempt < $max_attempts);
    
    return 'MBR' . date('YmdHis') . mt_rand(10, 99);
}

// Create upload directories if not exist
if (!file_exists(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}

// Create logs directory
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
?>
