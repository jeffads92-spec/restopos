<?php
// Start session
session_start();

// Environment Detection
define('ENVIRONMENT', getenv('RAILWAY_ENVIRONMENT') ?: 'development');

// Database Configuration
// Railway will auto-inject these environment variables when MySQL plugin is added
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'smart_resto_pos');
define('DB_PORT', getenv('MYSQL_PORT') ?: 3306);

// Application Configuration
define('APP_NAME', 'Smart Resto POS');
define('APP_VERSION', '1.0.0');

// Base URL - Auto detect with Railway support
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    // Railway environment
    $protocol = 'https';
    $host = getenv('RAILWAY_PUBLIC_DOMAIN');
    $base_path = '';
} else {
    // Local or other hosting
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = rtrim($base_path, '/');
}
define('BASE_URL', $protocol . '://' . $host . $base_path . '/');

// Upload Configuration
// Railway file system is ephemeral, so use /tmp for uploads
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

// Error Reporting based on environment
if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/tmp/php-error.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Session Configuration
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => ENVIRONMENT === 'production',
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Database Connection with error handling
try {
    // Create connection with port support
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Set timezone for MySQL
    $conn->query("SET time_zone = '+07:00'");
    
} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    
    if (ENVIRONMENT === 'development') {
        die("Database error: " . $e->getMessage() . "<br><br>
             <strong>Connection Details:</strong><br>
             Host: " . DB_HOST . ":" . DB_PORT . "<br>
             Database: " . DB_NAME . "<br>
             User: " . DB_USER);
    } else {
        die("
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Unavailable</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
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
                .icon { 
                    font-size: 64px; 
                    margin-bottom: 20px; 
                    animation: pulse 2s ease-in-out infinite;
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                h1 { 
                    color: #e74c3c; 
                    margin-bottom: 20px; 
                    font-size: 28px;
                }
                p { 
                    color: #555; 
                    line-height: 1.6; 
                    margin-bottom: 10px;
                }
                .retry-btn {
                    margin-top: 20px;
                    padding: 12px 30px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: all 0.3s;
                }
                .retry-btn:hover {
                    background: #5568d3;
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='icon'>⚠️</div>
                <h1>Service Unavailable</h1>
                <p>We're having trouble connecting to the database.</p>
                <p>Please try again in a few moments.</p>
                <button class='retry-btn' onclick='location.reload()'>Retry Connection</button>
            </div>
        </body>
        </html>
        ");
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

// Create directories if not exist (only in development)
if (ENVIRONMENT !== 'production') {
    if (!file_exists(UPLOAD_PATH)) {
        @mkdir(UPLOAD_PATH, 0755, true);
    }
    
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
} else {
    // In production (Railway), create /tmp directories
    if (!file_exists('/tmp/uploads/products')) {
        @mkdir('/tmp/uploads/products', 0755, true);
    }
}

// Force HTTPS in production
if (ENVIRONMENT === 'production' && 
    (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') &&
    (!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
?>
