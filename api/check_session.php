<?php
/**
 * Smart Resto POS - Check Session API
 * Checks if user session is valid
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$is_logged_in = isset($_SESSION['user_id']);
$session_data = [];

if ($is_logged_in) {
    // Check session timeout
    $inactive_time = 0;
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
    }
    
    $session_timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 7200;
    $is_expired = $inactive_time > $session_timeout;
    
    if ($is_expired) {
        // Session expired, destroy it
        session_unset();
        session_destroy();
        
        echo json_encode([
            'success' => false,
            'logged_in' => false,
            'expired' => true,
            'message' => 'Session expired'
        ]);
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Return session data
    $session_data = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'is_admin' => ($_SESSION['role'] ?? '') === 'admin',
        'last_activity' => $_SESSION['last_activity'] ?? 0,
        'session_timeout' => $session_timeout,
        'time_remaining' => $session_timeout - $inactive_time
    ];
}

echo json_encode([
    'success' => true,
    'logged_in' => $is_logged_in,
    'expired' => false,
    'session' => $session_data,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>
