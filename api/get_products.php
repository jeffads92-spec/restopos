<?php
/**
 * Smart Resto POS - Get Products API
 * Returns product list for POS with filtering options
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
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $is_active = isset($_GET['is_active']) ? intval($_GET['is_active']) : 1;
    
    // Build query
    $query = "SELECT 
                p.product_id,
                p.category_id,
                p.product_name,
                p.description,
                p.image,
                p.cost_price,
                p.selling_price,
                p.stock_quantity,
                p.min_stock,
                p.unit,
                p.is_active,
                c.category_name,
                c.icon as category_icon
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE p.is_active = ?";
    
    $params = [$is_active];
    $types = "i";
    
    // Add category filter
    if ($category_id > 0) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $query .= " ORDER BY c.category_name, p.product_name ASC";
    
    // Prepare and execute
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    // Bind parameters dynamically
    if (!empty($params)) {
        $bind_params = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $products = [];
    $categories_summary = [];
    
    while ($row = $result->fetch_assoc()) {
        // Determine stock status
        if ($row['stock_quantity'] <= 0) {
            $stock_status = 'out';
            $stock_status_label = 'Habis';
            $stock_status_class = 'danger';
        } elseif ($row['stock_quantity'] <= $row['min_stock']) {
            $stock_status = 'low';
            $stock_status_label = 'Menipis';
            $stock_status_class = 'warning';
        } else {
            $stock_status = 'ok';
            $stock_status_label = 'Aman';
            $stock_status_class = 'success';
        }
        
        // Format image path
        $image_url = null;
        if ($row['image']) {
            $image_path = __DIR__ . '/../uploads/products/' . $row['image'];
            if (file_exists($image_path)) {
                $image_url = '../uploads/products/' . $row['image'];
            }
        }
        
        // Calculate profit margin
        $cost_price = floatval($row['cost_price']);
        $selling_price = floatval($row['selling_price']);
        $profit_margin = 0;
        
        if ($cost_price > 0) {
            $profit_margin = (($selling_price - $cost_price) / $cost_price) * 100;
        }
        
        // Build product array
        $product = [
            'product_id' => intval($row['product_id']),
            'category_id' => intval($row['category_id']),
            'category_name' => $row['category_name'],
            'category_icon' => $row['category_icon'],
            'product_name' => $row['product_name'],
            'description' => $row['description'],
            'image' => $row['image'],
            'image_url' => $image_url,
            'cost_price' => $cost_price,
            'selling_price' => $selling_price,
            'stock_quantity' => intval($row['stock_quantity']),
            'min_stock' => intval($row['min_stock']),
            'unit' => $row['unit'],
            'is_active' => intval($row['is_active']),
            'stock_status' => $stock_status,
            'stock_status_label' => $stock_status_label,
            'stock_status_class' => $stock_status_class,
            'profit_margin' => round($profit_margin, 2),
            'can_sell' => $row['stock_quantity'] > 0
        ];
        
        $products[] = $product;
        
        // Count by category
        $cat_name = $row['category_name'] ?: 'Uncategorized';
        if (!isset($categories_summary[$cat_name])) {
            $categories_summary[$cat_name] = [
                'count' => 0,
                'total_stock' => 0
            ];
        }
        $categories_summary[$cat_name]['count']++;
        $categories_summary[$cat_name]['total_stock'] += intval($row['stock_quantity']);
    }
    
    $stmt->close();
    
    // Calculate summary
    $total_products = count($products);
    $in_stock = count(array_filter($products, function($p) {
        return $p['stock_quantity'] > 0;
    }));
    $low_stock = count(array_filter($products, function($p) {
        return $p['stock_status'] === 'low';
    }));
    $out_stock = count(array_filter($products, function($p) {
        return $p['stock_status'] === 'out';
    }));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'summary' => [
            'total' => $total_products,
            'in_stock' => $in_stock,
            'low_stock' => $low_stock,
            'out_stock' => $out_stock
        ],
        'categories_summary' => $categories_summary,
        'filters' => [
            'category_id' => $category_id,
            'search' => $search,
            'is_active' => $is_active
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get products error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve products: ' . $e->getMessage(),
        'products' => []
    ]);
}

$conn->close();
?>