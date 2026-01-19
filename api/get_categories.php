<?php
/**
 * Smart Resto POS - Get Categories API
 * Returns category list for POS filtering
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

try {
    // Get filter parameters
    $is_active = isset($_GET['is_active']) ? intval($_GET['is_active']) : 1;
    
    // Build query
    $query = "SELECT 
                category_id,
                category_name,
                description,
                icon,
                is_active,
                created_at
              FROM categories
              WHERE is_active = ?
              ORDER BY category_name ASC";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $is_active);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        // Count products in category
        $count_query = "SELECT COUNT(*) as product_count 
                       FROM products 
                       WHERE category_id = ? AND is_active = 1";
        $stmt2 = $conn->prepare($count_query);
        $cat_id = $row['category_id'];
        $stmt2->bind_param('i', $cat_id);
        $stmt2->execute();
        $count_result = $stmt2->get_result();
        $product_count = $count_result->fetch_assoc()['product_count'];
        $stmt2->close();
        
        $categories[] = [
            'category_id' => intval($row['category_id']),
            'category_name' => $row['category_name'],
            'description' => $row['description'],
            'icon' => $row['icon'] ?? 'fa-tag',
            'is_active' => intval($row['is_active']),
            'product_count' => intval($product_count),
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'total' => count($categories)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get categories error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve categories: ' . $e->getMessage(),
        'categories' => []
    ]);
}

$conn->close();
?>