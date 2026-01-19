<?php
/**
 * Smart Resto POS - Get Transaction Detail API
 * Returns detailed transaction information with items
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

// Get and validate transaction ID
$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transaction_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid transaction ID'
    ]);
    exit;
}

try {
    // Get transaction details
    $query = "SELECT 
                t.*,
                u.full_name,
                u.username,
                m.member_name,
                m.member_code
              FROM transactions t
              LEFT JOIN users u ON t.user_id = u.user_id
              LEFT JOIN members m ON t.member_id = m.member_id
              WHERE t.transaction_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $transaction_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Transaction not found'
        ]);
        exit;
    }
    
    $transaction = $result->fetch_assoc();
    $stmt->close();
    
    // Format transaction data
    $transaction['transaction_date'] = date('d/m/Y H:i', strtotime($transaction['transaction_date']));
    $transaction['subtotal'] = floatval($transaction['subtotal']);
    $transaction['discount'] = floatval($transaction['discount']);
    $transaction['tax'] = floatval($transaction['tax']);
    $transaction['total_amount'] = floatval($transaction['total_amount']);
    $transaction['cash_received'] = floatval($transaction['cash_received']);
    $transaction['change_amount'] = floatval($transaction['change_amount']);
    $transaction['points_earned'] = intval($transaction['points_earned']);
    
    // Get transaction items
    $query_items = "SELECT 
                        ti.*,
                        p.product_name as db_product_name,
                        p.image
                    FROM transaction_items ti
                    LEFT JOIN products p ON ti.product_id = p.product_id
                    WHERE ti.transaction_id = ?
                    ORDER BY ti.item_id";
    
    $stmt2 = $conn->prepare($query_items);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare items query: ' . $conn->error);
    }
    
    $stmt2->bind_param('i', $transaction_id);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to get items: ' . $stmt2->error);
    }
    
    $result_items = $stmt2->get_result();
    
    $items = [];
    while ($item = $result_items->fetch_assoc()) {
        // Format item data
        $item['quantity'] = intval($item['quantity']);
        $item['unit_price'] = floatval($item['unit_price']);
        $item['subtotal'] = floatval($item['subtotal']);
        
        // Add image URL if exists
        if ($item['image'] && file_exists(__DIR__ . '/../uploads/products/' . $item['image'])) {
            $item['image_url'] = '../uploads/products/' . $item['image'];
        } else {
            $item['image_url'] = null;
        }
        
        $items[] = $item;
    }
    
    $stmt2->close();
    
    // Calculate summary
    $items_count = count($items);
    $total_items = array_sum(array_column($items, 'quantity'));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'transaction' => $transaction,
        'items' => $items,
        'summary' => [
            'items_count' => $items_count,
            'total_items' => $total_items,
            'subtotal' => $transaction['subtotal'],
            'discount' => $transaction['discount'],
            'tax' => $transaction['tax'],
            'total' => $transaction['total_amount']
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get transaction detail error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve transaction details: ' . $e->getMessage()
    ]);
}

$conn->close();
?>