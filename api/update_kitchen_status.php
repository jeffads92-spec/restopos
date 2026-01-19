<?php
/**
 * Smart Resto POS - Update Kitchen Status API
 * Updates order item status in kitchen display
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access'
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
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';

// Validate parameters
if ($item_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID'
    ]);
    exit;
}

$valid_statuses = ['pending', 'preparing', 'ready', 'served'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status. Must be: ' . implode(', ', $valid_statuses)
    ]);
    exit;
}

try {
    // Check if item exists
    $check_query = "SELECT item_id, status, product_name FROM transaction_items WHERE item_id = ?";
    $stmt = $conn->prepare($check_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare check query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Item not found'
        ]);
        exit;
    }
    
    $item = $result->fetch_assoc();
    $old_status = $item['status'];
    $stmt->close();
    
    // Update status
    $update_query = "UPDATE transaction_items SET status = ?, updated_at = NOW() WHERE item_id = ?";
    $stmt2 = $conn->prepare($update_query);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare update query: ' . $conn->error);
    }
    
    $stmt2->bind_param('si', $status, $item_id);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to update status: ' . $stmt2->error);
    }
    
    $stmt2->close();
    
    // Log activity
    error_log(sprintf(
        'Kitchen status updated: Item #%d (%s) changed from %s to %s by user #%d',
        $item_id,
        $item['product_name'],
        $old_status,
        $status,
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'data' => [
            'item_id' => $item_id,
            'old_status' => $old_status,
            'new_status' => $status,
            'product_name' => $item['product_name']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Update kitchen status error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $e->getMessage()
    ]);
}

$conn->close();
?>