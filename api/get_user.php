<?php
/**
 * Smart Resto POS - Get User API (FIXED)
 * Returns user details for editing
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit;
}

// Get and validate user ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

try {
    // Get user details
    $query = "SELECT 
                user_id,
                username,
                full_name,
                email,
                phone,
                role,
                is_active,
                created_at,
                updated_at
              FROM users 
              WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Format dates
    $user['created_at'] = date('d/m/Y H:i', strtotime($user['created_at']));
    $user['updated_at'] = date('d/m/Y H:i', strtotime($user['updated_at']));
    
    // Convert is_active to boolean
    $user['is_active'] = (bool) $user['is_active'];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log('Get user error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve user'
    ]);
}

$conn->close();
?>