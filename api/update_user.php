<?php
/**
 * Smart Resto POS - Update User API
 * Admin-only endpoint to update existing users
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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validation
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'User ID tidak valid';
}

if (empty($username)) {
    $errors[] = 'Username wajib diisi';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username minimal 3 karakter';
}

if (empty($full_name)) {
    $errors[] = 'Nama lengkap wajib diisi';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}

if (!empty($password) && strlen($password) < 6) {
    $errors[] = 'Password minimal 6 karakter';
}

$valid_roles = ['admin', 'kasir'];
if (!in_array($role, $valid_roles)) {
    $errors[] = 'Role tidak valid';
}

// Return validation errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validasi gagal',
        'errors' => $errors
    ]);
    exit;
}

try {
    // Check if user exists
    $check_query = "SELECT user_id, username FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare check query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ]);
        exit;
    }
    
    $stmt->close();
    
    // Check if username is taken by another user
    $check_username = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $stmt2 = $conn->prepare($check_username);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare username check: ' . $conn->error);
    }
    
    $stmt2->bind_param('si', $username, $user_id);
    $stmt2->execute();
    $username_result = $stmt2->get_result();
    
    if ($username_result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username sudah digunakan oleh user lain'
        ]);
        exit;
    }
    
    $stmt2->close();
    
    // Update user - with or without password
    if (!empty($password)) {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $update_query = "UPDATE users SET 
            username = ?,
            password = ?,
            full_name = ?,
            email = ?,
            phone = ?,
            role = ?,
            is_active = ?,
            updated_at = NOW()
            WHERE user_id = ?";
        
        $stmt3 = $conn->prepare($update_query);
        
        if (!$stmt3) {
            throw new Exception('Failed to prepare update query: ' . $conn->error);
        }
        
        $stmt3->bind_param('ssssssii', $username, $hashed_password, $full_name, $email, $phone, $role, $is_active, $user_id);
    } else {
        // Update without changing password
        $update_query = "UPDATE users SET 
            username = ?,
            full_name = ?,
            email = ?,
            phone = ?,
            role = ?,
            is_active = ?,
            updated_at = NOW()
            WHERE user_id = ?";
        
        $stmt3 = $conn->prepare($update_query);
        
        if (!$stmt3) {
            throw new Exception('Failed to prepare update query: ' . $conn->error);
        }
        
        $stmt3->bind_param('sssssii', $username, $full_name, $email, $phone, $role, $is_active, $user_id);
    }
    
    if (!$stmt3->execute()) {
        throw new Exception('Failed to update user: ' . $stmt3->error);
    }
    
    $stmt3->close();
    
    // Log activity
    $password_changed = !empty($password) ? 'with password change' : 'without password change';
    error_log(sprintf(
        'User updated: ID #%d, Username: %s %s by Admin #%d',
        $user_id,
        $username,
        $password_changed,
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'User berhasil diupdate',
        'data' => [
            'user_id' => $user_id,
            'username' => $username,
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => $is_active,
            'password_changed' => !empty($password)
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Update user error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengupdate user: ' . $e->getMessage()
    ]);
}

$conn->close();
?>