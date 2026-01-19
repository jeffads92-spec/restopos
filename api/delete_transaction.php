<?php
/**
 * Smart Resto POS - Delete Transaction API
 * Admin-only endpoint to delete transactions and restore stock
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

$transaction_id = isset($data['id']) ? intval($data['id']) : 0;

if ($transaction_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Transaction ID tidak valid'
    ]);
    exit;
}

// Start database transaction
if (!$conn->begin_transaction()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to start database transaction'
    ]);
    exit;
}

try {
    // Get transaction details before deletion
    $trans_query = "SELECT 
                        t.transaction_id,
                        t.transaction_code,
                        t.total_amount,
                        t.member_id,
                        t.points_earned,
                        t.status
                    FROM transactions t
                    WHERE t.transaction_id = ?";
    
    $stmt = $conn->prepare($trans_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare transaction query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $trans_result = $stmt->get_result();
    
    if ($trans_result->num_rows === 0) {
        throw new Exception('Transaksi tidak ditemukan');
    }
    
    $transaction = $trans_result->fetch_assoc();
    $stmt->close();
    
    // Get transaction items to restore stock
    $items_query = "SELECT 
                        ti.product_id,
                        ti.product_name,
                        ti.quantity
                    FROM transaction_items ti
                    WHERE ti.transaction_id = ?";
    
    $stmt2 = $conn->prepare($items_query);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare items query: ' . $conn->error);
    }
    
    $stmt2->bind_param('i', $transaction_id);
    $stmt2->execute();
    $items_result = $stmt2->get_result();
    
    $items_restored = [];
    
    // Restore stock for each item
    while ($item = $items_result->fetch_assoc()) {
        $product_id = intval($item['product_id']);
        $quantity = intval($item['quantity']);
        
        // Get current stock
        $stock_query = "SELECT stock_quantity FROM products WHERE product_id = ?";
        $stmt3 = $conn->prepare($stock_query);
        
        if (!$stmt3) {
            throw new Exception('Failed to prepare stock query: ' . $conn->error);
        }
        
        $stmt3->bind_param('i', $product_id);
        $stmt3->execute();
        $stock_result = $stmt3->get_result();
        
        if ($stock_result->num_rows > 0) {
            $stock_data = $stock_result->fetch_assoc();
            $current_stock = intval($stock_data['stock_quantity']);
            $new_stock = $current_stock + $quantity;
            
            $stmt3->close();
            
            // Update stock
            $update_stock = "UPDATE products SET 
                            stock_quantity = ?,
                            updated_at = NOW()
                            WHERE product_id = ?";
            
            $stmt4 = $conn->prepare($update_stock);
            
            if (!$stmt4) {
                throw new Exception('Failed to prepare stock update: ' . $conn->error);
            }
            
            $stmt4->bind_param('ii', $new_stock, $product_id);
            
            if (!$stmt4->execute()) {
                throw new Exception('Failed to update stock: ' . $stmt4->error);
            }
            
            $stmt4->close();
            
            // Add stock history
            $history_query = "INSERT INTO stock_history 
                            (product_id, type, quantity, stock_before, stock_after, notes, created_by, created_at) 
                            VALUES (?, 'in', ?, ?, ?, ?, ?, NOW())";
            
            $stmt5 = $conn->prepare($history_query);
            
            if ($stmt5) {
                $notes = "Pengembalian dari transaksi yang dihapus: " . $transaction['transaction_code'];
                $user_id = $_SESSION['user_id'];
                
                $stmt5->bind_param('iiissi', $product_id, $quantity, $current_stock, $new_stock, $notes, $user_id);
                $stmt5->execute();
                $stmt5->close();
            }
            
            $items_restored[] = [
                'product_id' => $product_id,
                'product_name' => $item['product_name'],
                'quantity_restored' => $quantity,
                'stock_before' => $current_stock,
                'stock_after' => $new_stock
            ];
        }
    }
    
    $stmt2->close();
    
    // Restore member points if applicable
    $points_restored = 0;
    if ($transaction['member_id'] && $transaction['points_earned'] > 0) {
        $restore_points = "UPDATE members SET 
                          points = points - ?,
                          total_spent = total_spent - ?,
                          updated_at = NOW()
                          WHERE member_id = ?";
        
        $stmt6 = $conn->prepare($restore_points);
        
        if ($stmt6) {
            $points = intval($transaction['points_earned']);
            $total = floatval($transaction['total_amount']);
            $member_id = intval($transaction['member_id']);
            
            $stmt6->bind_param('idi', $points, $total, $member_id);
            $stmt6->execute();
            $stmt6->close();
            
            $points_restored = $points;
        }
    }
    
    // Delete stock history related to this transaction
    $delete_history = "DELETE FROM stock_history WHERE transaction_id = ?";
    $stmt7 = $conn->prepare($delete_history);
    
    if ($stmt7) {
        $stmt7->bind_param('i', $transaction_id);
        $stmt7->execute();
        $stmt7->close();
    }
    
    // Delete transaction items
    $delete_items = "DELETE FROM transaction_items WHERE transaction_id = ?";
    $stmt8 = $conn->prepare($delete_items);
    
    if (!$stmt8) {
        throw new Exception('Failed to prepare delete items: ' . $conn->error);
    }
    
    $stmt8->bind_param('i', $transaction_id);
    
    if (!$stmt8->execute()) {
        throw new Exception('Failed to delete items: ' . $stmt8->error);
    }
    
    $stmt8->close();
    
    // Delete transaction
    $delete_trans = "DELETE FROM transactions WHERE transaction_id = ?";
    $stmt9 = $conn->prepare($delete_trans);
    
    if (!$stmt9) {
        throw new Exception('Failed to prepare delete transaction: ' . $conn->error);
    }
    
    $stmt9->bind_param('i', $transaction_id);
    
    if (!$stmt9->execute()) {
        throw new Exception('Failed to delete transaction: ' . $stmt9->error);
    }
    
    $stmt9->close();
    
    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction: ' . $conn->error);
    }
    
    // Log activity
    error_log(sprintf(
        'Transaction deleted: %s, %d items restored, %d points restored by Admin #%d',
        $transaction['transaction_code'],
        count($items_restored),
        $points_restored,
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil dihapus dan stok dikembalikan',
        'data' => [
            'transaction_code' => $transaction['transaction_code'],
            'items_restored' => count($items_restored),
            'points_restored' => $points_restored,
            'details' => $items_restored
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    error_log('Delete transaction error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete transaction: ' . $e->getMessage()
    ]);
}

$conn->close();
?>