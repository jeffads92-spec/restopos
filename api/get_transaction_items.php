<?php
/**
 * Smart Resto POS - Get Transaction Items API
 * Returns items for a specific transaction
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
        'message' => 'Transaction ID tidak valid'
    ]);
    exit;
}

try {
    // First, verify transaction exists and user has access
    $trans_check = "SELECT 
                        t.transaction_id,
                        t.transaction_code,
                        t.user_id
                    FROM transactions t
                    WHERE t.transaction_id = ?";
    
    $stmt = $conn->prepare($trans_check);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare transaction check: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $trans_result = $stmt->get_result();
    
    if ($trans_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Transaksi tidak ditemukan'
        ]);
        exit;
    }
    
    $transaction = $trans_result->fetch_assoc();
    $stmt->close();
    
    // Get transaction items with product details
    $query = "SELECT 
                ti.item_id,
                ti.transaction_id,
                ti.product_id,
                ti.product_name,
                ti.quantity,
                ti.unit_price,
                ti.subtotal,
                ti.notes,
                ti.status,
                ti.created_at,
                ti.updated_at,
                p.image,
                p.unit,
                p.category_id,
                c.category_name
              FROM transaction_items ti
              LEFT JOIN products p ON ti.product_id = p.product_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE ti.transaction_id = ?
              ORDER BY ti.item_id";
    
    $stmt2 = $conn->prepare($query);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare items query: ' . $conn->error);
    }
    
    $stmt2->bind_param('i', $transaction_id);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to execute items query: ' . $stmt2->error);
    }
    
    $result = $stmt2->get_result();
    
    $items = [];
    $total_quantity = 0;
    $total_amount = 0;
    
    while ($item = $result->fetch_assoc()) {
        // Format numeric values
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        $subtotal = floatval($item['subtotal']);
        
        // Add to totals
        $total_quantity += $quantity;
        $total_amount += $subtotal;
        
        // Format image URL
        $image_url = null;
        if ($item['image']) {
            $image_path = __DIR__ . '/../uploads/products/' . $item['image'];
            if (file_exists($image_path)) {
                $image_url = '../uploads/products/' . $item['image'];
            }
        }
        
        // Build item array
        $items[] = [
            'item_id' => intval($item['item_id']),
            'transaction_id' => intval($item['transaction_id']),
            'product_id' => intval($item['product_id']),
            'product_name' => $item['product_name'],
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'subtotal' => $subtotal,
            'notes' => $item['notes'],
            'status' => $item['status'],
            'unit' => $item['unit'],
            'category_name' => $item['category_name'],
            'image_url' => $image_url,
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ];
    }
    
    $stmt2->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'transaction' => [
            'transaction_id' => intval($transaction['transaction_id']),
            'transaction_code' => $transaction['transaction_code']
        ],
        'items' => $items,
        'summary' => [
            'total_items' => count($items),
            'total_quantity' => $total_quantity,
            'total_amount' => $total_amount
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get transaction items error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve items: ' . $e->getMessage(),
        'items' => []
    ]);
}

$conn->close();
?>