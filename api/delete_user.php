<?php
/**
 * Smart Resto POS - Delete User API
 * Admin-only endpoint to delete users (soft delete recommended)
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

// Get and validate input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

$user_id = isset($data['id']) ? intval($data['id']) : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID tidak valid'
    ]);
    exit;
}

// Prevent deleting self
if ($user_id == $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak dapat menghapus akun sendiri'
    ]);
    exit;
}

try {
    // Check if user exists and get info
    $check_query = "SELECT user_id, username, full_name, role FROM users WHERE user_id = ?";
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
    
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user has transactions (prevent deletion if has history)
    $trans_check = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ?";
    $stmt2 = $conn->prepare($trans_check);
    
    if ($stmt2) {
        $stmt2->bind_param('i', $user_id);
        $stmt2->execute();
        $trans_result = $stmt2->get_result();
        $trans_count = $trans_result->fetch_assoc()['count'];
        $stmt2->close();
        
        if ($trans_count > 0) {
            // Soft delete instead of hard delete
            $soft_delete = "UPDATE users SET 
                is_active = 0,
                username = CONCAT(username, '_deleted_', ?),
                updated_at = NOW()
                WHERE user_id = ?";
            
            $stmt3 = $conn->prepare($soft_delete);
            
            if (!$stmt3) {
                throw new Exception('Failed to prepare soft delete: ' . $conn->error);
            }
            
            $timestamp = time();
            $stmt3->bind_param('ii', $timestamp, $user_id);
            
            if (!$stmt3->execute()) {
                throw new Exception('Failed to soft delete user: ' . $stmt3->error);
            }
            
            $stmt3->close();
            
            // Log activity
            error_log(sprintf(
                'User soft deleted (has %d transactions): ID #%d, Username: %s by Admin #%d',
                $trans_count,
                $user_id,
                $user_data['username'],
                $_SESSION['user_id']
            ));
            
            echo json_encode([
                'success' => true,
                'message' => 'User berhasil dinonaktifkan (memiliki riwayat transaksi)',
                'soft_delete' => true,
                'transaction_count' => $trans_count
            ]);
            exit;
        }
    }
    
    // Hard delete if no transactions
    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $stmt4 = $conn->prepare($delete_query);
    
    if (!$stmt4) {
        throw new Exception('Failed to prepare delete query: ' . $conn->error);
    }
    
    $stmt4->bind_param('i', $user_id);
    
    if (!$stmt4->execute()) {
        throw new Exception('Failed to delete user: ' . $stmt4->error);
    }
    
    $stmt4->close();
    
    // Log activity
    error_log(sprintf(
        'User deleted: ID #%d, Username: %s, Role: %s by Admin #%d',
        $user_id,
        $user_data['username'],
        $user_data['role'],
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'User berhasil dihapus',
        'soft_delete' => false,
        'data' => [
            'user_id' => $user_id,
            'username' => $user_data['username']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Delete user error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus user: ' . $e->getMessage()
    ]);
}

$conn->close();
?>