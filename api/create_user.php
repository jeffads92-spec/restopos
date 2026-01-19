<?php
/**
 * Smart Resto POS - Create User API
 * Admin-only endpoint to create new users
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
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : 'kasir';
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validation
$errors = [];

if (empty($username)) {
    $errors[] = 'Username wajib diisi';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username minimal 3 karakter';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username hanya boleh huruf, angka, dan underscore';
}

if (empty($full_name)) {
    $errors[] = 'Nama lengkap wajib diisi';
} elseif (strlen($full_name) < 3) {
    $errors[] = 'Nama lengkap minimal 3 karakter';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}

if (empty($password)) {
    $errors[] = 'Password wajib diisi';
} elseif (strlen($password) < 6) {
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
    // Check if username already exists
    $check_query = "SELECT user_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare check query: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username sudah digunakan'
        ]);
        exit;
    }
    
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert user
    $insert_query = "INSERT INTO users 
        (username, password, full_name, email, phone, role, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt2 = $conn->prepare($insert_query);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare insert query: ' . $conn->error);
    }
    
    $stmt2->bind_param('ssssssi', $username, $hashed_password, $full_name, $email, $phone, $role, $is_active);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to create user: ' . $stmt2->error);
    }
    
    $new_user_id = $stmt2->insert_id;
    $stmt2->close();
    
    // Log activity
    error_log(sprintf(
        'New user created: ID #%d, Username: %s, Role: %s by Admin #%d',
        $new_user_id,
        $username,
        $role,
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'User berhasil ditambahkan',
        'data' => [
            'user_id' => $new_user_id,
            'username' => $username,
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => $is_active
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Create user error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menambahkan user: ' . $e->getMessage()
    ]);
}

$conn->close();
?>