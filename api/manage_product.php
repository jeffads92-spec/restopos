<?php
/**
 * Smart Resto POS - Manage Product API
 * Handles product CRUD operations via AJAX
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Admin only for write operations
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['add', 'edit', 'delete']) && !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit;
}

try {
    switch ($action) {
        case 'add':
            // Validate input
            $category_id = intval($_POST['category_id'] ?? 0);
            $product_name = trim($_POST['product_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $cost_price = floatval($_POST['cost_price'] ?? 0);
            $selling_price = floatval($_POST['selling_price'] ?? 0);
            $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
            $min_stock = intval($_POST['min_stock'] ?? 5);
            $unit = trim($_POST['unit'] ?? 'pcs');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($product_name)) {
                throw new Exception('Nama produk wajib diisi');
            }
            
            if ($category_id <= 0) {
                throw new Exception('Kategori harus dipilih');
            }
            
            if ($selling_price <= 0) {
                throw new Exception('Harga jual harus lebih dari 0');
            }
            
            // Handle image upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['image']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF');
                }
                
                if ($_FILES['image']['size'] > MAX_FILE_SIZE) {
                    throw new Exception('Ukuran gambar terlalu besar (max 5MB)');
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('prod_') . '.' . $ext;
                $upload_path = UPLOAD_PATH . $image_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception('Gagal upload gambar');
                }
            }
            
            // Insert product
            $query = "INSERT INTO products 
                (category_id, product_name, description, image, cost_price, selling_price, 
                 stock_quantity, min_stock, unit, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isssddiisi', 
                $category_id, $product_name, $description, $image_name, 
                $cost_price, $selling_price, $stock_quantity, $min_stock, $unit, $is_active
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal menambah produk: ' . $stmt->error);
            }
            
            $product_id = $stmt->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan',
                'product_id' => $product_id
            ]);
            break;
            
        case 'edit':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                throw new Exception('ID produk tidak valid');
            }
            
            // Get current product data
            $check = $conn->query("SELECT image FROM products WHERE product_id = $product_id");
            if ($check->num_rows === 0) {
                throw new Exception('Produk tidak ditemukan');
            }
            $current = $check->fetch_assoc();
            
            $category_id = intval($_POST['category_id'] ?? 0);
            $product_name = trim($_POST['product_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $cost_price = floatval($_POST['cost_price'] ?? 0);
            $selling_price = floatval($_POST['selling_price'] ?? 0);
            $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
            $min_stock = intval($_POST['min_stock'] ?? 5);
            $unit = trim($_POST['unit'] ?? 'pcs');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle image upload
            $image_name = $current['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['image']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Format gambar tidak didukung');
                }
                
                if ($_FILES['image']['size'] > MAX_FILE_SIZE) {
                    throw new Exception('Ukuran gambar terlalu besar');
                }
                
                // Delete old image
                if ($image_name && file_exists(UPLOAD_PATH . $image_name)) {
                    unlink(UPLOAD_PATH . $image_name);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('prod_') . '.' . $ext;
                $upload_path = UPLOAD_PATH . $image_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception('Gagal upload gambar');
                }
            }
            
            // Update product
            $query = "UPDATE products SET 
                category_id = ?, product_name = ?, description = ?, image = ?, 
                cost_price = ?, selling_price = ?, stock_quantity = ?, min_stock = ?, 
                unit = ?, is_active = ?, updated_at = NOW()
                WHERE product_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isssddiisii', 
                $category_id, $product_name, $description, $image_name, 
                $cost_price, $selling_price, $stock_quantity, $min_stock, 
                $unit, $is_active, $product_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal update produk: ' . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Produk berhasil diupdate'
            ]);
            break;
            
        case 'delete':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                throw new Exception('ID produk tidak valid');
            }
            
            // Check if product has transaction history
            $check = $conn->query("SELECT COUNT(*) as count FROM transaction_items WHERE product_id = $product_id");
            $count = $check->fetch_assoc()['count'];
            
            if ($count > 0) {
                // Soft delete - just deactivate
                $conn->query("UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = $product_id");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk dinonaktifkan (memiliki riwayat transaksi)',
                    'soft_delete' => true
                ]);
            } else {
                // Hard delete - get image first
                $img_query = $conn->query("SELECT image FROM products WHERE product_id = $product_id");
                if ($img_query->num_rows > 0) {
                    $img_data = $img_query->fetch_assoc();
                    if ($img_data['image'] && file_exists(UPLOAD_PATH . $img_data['image'])) {
                        unlink(UPLOAD_PATH . $img_data['image']);
                    }
                }
                
                $conn->query("DELETE FROM products WHERE product_id = $product_id");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk berhasil dihapus',
                    'soft_delete' => false
                ]);
            }
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    error_log('Manage product error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>