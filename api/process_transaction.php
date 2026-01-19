<?php
/**
 * Smart Resto POS - Process Transaction API
 * COMPLETE FIXED VERSION - Clean, Simple, Works!
 */

// Clean start - no output before this
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/response_helper.php';

// Clear any previous output
ob_end_clean();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check authentication
ApiRequest::requireAuth();

// Check method
ApiRequest::requireMethod('POST');

// Get JSON input
$data = ApiRequest::getJsonInput();

if (!$data) {
    ApiResponse::error('No data received');
}

// Validate required fields
$errors = [];

if (empty($data['items']) || !is_array($data['items'])) {
    $errors[] = 'Keranjang kosong';
}

if (empty($data['payment_method'])) {
    $errors[] = 'Metode pembayaran wajib dipilih';
}

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

// Extract and validate data
$items = $data['items'];
$member_id = !empty($data['member_id']) ? intval($data['member_id']) : null;
$payment_method = strtolower(trim($data['payment_method']));
$cash_received = floatval($data['cash_received'] ?? 0);
$subtotal = floatval($data['subtotal'] ?? 0);
$tax = floatval($data['tax'] ?? 0);
$total = floatval($data['total'] ?? 0);
$discount = floatval($data['discount'] ?? 0);
$user_id = intval($_SESSION['user_id']);

// Validate payment method
$valid_methods = ['cash', 'qris', 'transfer', 'debit', 'credit'];
if (!in_array($payment_method, $valid_methods)) {
    ApiResponse::error('Metode pembayaran tidak valid');
}

// Validate amounts
if ($total <= 0) {
    ApiResponse::error('Total tidak valid');
}

if ($payment_method === 'cash' && $cash_received < $total) {
    ApiResponse::error('Jumlah uang tidak mencukupi');
}

// Start database transaction
$conn->begin_transaction();

try {
    // 1. Generate unique transaction code
    $transaction_code = generateTransactionCode($conn);
    
    // 2. Calculate points for member
    $points_earned = 0;
    if ($member_id) {
        $points_per_thousand = defined('POINTS_PER_1000') ? intval(POINTS_PER_1000) : 1;
        $points_earned = floor($total / 1000) * $points_per_thousand;
    }
    
    // 3. Calculate change
    $change = ($payment_method === 'cash') ? ($cash_received - $total) : 0;
    $transaction_date = date('Y-m-d H:i:s');
    
    // 4. Insert transaction
    $query = "INSERT INTO transactions 
        (transaction_code, user_id, member_id, transaction_date, subtotal, discount, tax, 
         total_amount, payment_method, cash_received, change_amount, points_earned, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare transaction: ' . $conn->error);
    }
    
    $stmt->bind_param('siisddddsddi',
        $transaction_code,
        $user_id,
        $member_id,
        $transaction_date,
        $subtotal,
        $discount,
        $tax,
        $total,
        $payment_method,
        $cash_received,
        $change,
        $points_earned
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert transaction: ' . $stmt->error);
    }
    
    $transaction_id = $stmt->insert_id;
    $stmt->close();
    
    if (!$transaction_id) {
        throw new Exception('Failed to get transaction ID');
    }
    
    // 5. Process each item
    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['selling_price']);
        $product_name = $conn->real_escape_string($item['product_name']);
        $item_subtotal = $quantity * $unit_price;
        
        // a. Check stock availability
        $stock_query = "SELECT stock_quantity, product_name FROM products WHERE product_id = ?";
        $stmt2 = $conn->prepare($stock_query);
        $stmt2->bind_param('i', $product_id);
        $stmt2->execute();
        $stock_result = $stmt2->get_result();
        
        if ($stock_result->num_rows === 0) {
            throw new Exception("Produk tidak ditemukan (ID: $product_id)");
        }
        
        $stock_row = $stock_result->fetch_assoc();
        $current_stock = intval($stock_row['stock_quantity']);
        $stmt2->close();
        
        if ($current_stock < $quantity) {
            throw new Exception("Stok tidak mencukupi untuk: {$stock_row['product_name']}");
        }
        
        // b. Insert transaction item
        $item_query = "INSERT INTO transaction_items 
            (transaction_id, product_id, product_name, quantity, unit_price, subtotal, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt3 = $conn->prepare($item_query);
        $stmt3->bind_param('iisidd', $transaction_id, $product_id, $product_name, $quantity, $unit_price, $item_subtotal);
        
        if (!$stmt3->execute()) {
            throw new Exception('Failed to insert item: ' . $stmt3->error);
        }
        $stmt3->close();
        
        // c. Update product stock
        $new_stock = $current_stock - $quantity;
        $update_query = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE product_id = ?";
        $stmt4 = $conn->prepare($update_query);
        $stmt4->bind_param('ii', $new_stock, $product_id);
        
        if (!$stmt4->execute()) {
            throw new Exception('Failed to update stock: ' . $stmt4->error);
        }
        $stmt4->close();
        
        // d. Insert stock history
        $history_query = "INSERT INTO stock_history 
            (product_id, transaction_id, type, quantity, stock_before, stock_after, notes, created_by, created_at) 
            VALUES (?, ?, 'out', ?, ?, ?, 'Penjualan via POS', ?, NOW())";
        
        $stmt5 = $conn->prepare($history_query);
        $stmt5->bind_param('iiiiii', $product_id, $transaction_id, $quantity, $current_stock, $new_stock, $user_id);
        $stmt5->execute();
        $stmt5->close();
    }
    
    // 6. Update member points and spending
    if ($member_id && $points_earned > 0) {
        $update_member = "UPDATE members SET 
            points = points + ?, 
            total_spent = total_spent + ?,
            updated_at = NOW()
            WHERE member_id = ?";
        
        $stmt6 = $conn->prepare($update_member);
        $stmt6->bind_param('idi', $points_earned, $total, $member_id);
        $stmt6->execute();
        $stmt6->close();
    }
    
    // 7. Commit transaction
    $conn->commit();
    
    // 8. Log success
    error_log(sprintf(
        'Transaction success: %s - Total: %.2f - Items: %d - User: %d',
        $transaction_code,
        $total,
        count($items),
        $user_id
    ));
    
    // 9. Send success response
    ApiResponse::success('Transaksi berhasil!', [
        'transaction_id' => $transaction_id,
        'transaction_code' => $transaction_code,
        'total_amount' => $total,
        'cash_received' => $cash_received,
        'change' => $change,
        'points_earned' => $points_earned,
        'payment_method' => $payment_method,
        'items_count' => count($items)
    ]);
    
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    
    // Log error
    error_log('Transaction error: ' . $e->getMessage());
    
    // Send error response
    ApiResponse::error('Transaksi gagal: ' . $e->getMessage(), 400);
}

$conn->close();
?>