<?php
/**
 * Smart Resto POS - Update Stock API
 * Admin-only endpoint to update product stock
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
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validation
$errors = [];

if ($product_id <= 0) {
    $errors[] = 'Product ID tidak valid';
}

$valid_types = ['in', 'out', 'adjustment'];
if (!in_array($type, $valid_types)) {
    $errors[] = 'Tipe stok tidak valid (gunakan: in, out, atau adjustment)';
}

if ($quantity <= 0) {
    $errors[] = 'Jumlah harus lebih dari 0';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validasi gagal',
        'errors' => $errors
    ]);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current product data
    $query = "SELECT product_id, product_name, stock_quantity FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Produk tidak ditemukan');
    }
    
    $product = $result->fetch_assoc();
    $current_stock = intval($product['stock_quantity']);
    $stmt->close();
    
    // Calculate new stock
    switch ($type) {
        case 'in':
            $new_stock = $current_stock + $quantity;
            $actual_quantity = $quantity;
            break;
        case 'out':
            $new_stock = $current_stock - $quantity;
            $actual_quantity = $quantity;
            if ($new_stock < 0) {
                throw new Exception('Stok tidak mencukupi');
            }
            break;
        case 'adjustment':
            $new_stock = $quantity;
            $actual_quantity = abs($quantity - $current_stock);
            break;
        default:
            throw new Exception('Tipe tidak valid');
    }
    
    // Update stock
    $update_query = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE product_id = ?";
    $stmt2 = $conn->prepare($update_query);
    
    if (!$stmt2) {
        throw new Exception('Failed to prepare update: ' . $conn->error);
    }
    
    $stmt2->bind_param('ii', $new_stock, $product_id);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to update stock: ' . $stmt2->error);
    }
    
    $stmt2->close();
    
    // Insert stock history
    $history_query = "INSERT INTO stock_history 
        (product_id, type, quantity, stock_before, stock_after, notes, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt3 = $conn->prepare($history_query);
    
    if (!$stmt3) {
        throw new Exception('Failed to prepare history: ' . $conn->error);
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt3->bind_param('isiissi', $product_id, $type, $actual_quantity, $current_stock, $new_stock, $notes, $user_id);
    
    if (!$stmt3->execute()) {
        throw new Exception('Failed to insert history: ' . $stmt3->error);
    }
    
    $stmt3->close();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    error_log(sprintf(
        'Stock updated: Product #%d (%s) - Type: %s, Qty: %d, Before: %d, After: %d by User #%d',
        $product_id,
        $product['product_name'],
        $type,
        $actual_quantity,
        $current_stock,
        $new_stock,
        $user_id
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Stok berhasil diupdate',
        'data' => [
            'product_id' => $product_id,
            'product_name' => $product['product_name'],
            'type' => $type,
            'quantity' => $actual_quantity,
            'stock_before' => $current_stock,
            'stock_after' => $new_stock
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    error_log('Update stock error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal update stok: ' . $e->getMessage()
    ]);
}

$conn->close();
?>