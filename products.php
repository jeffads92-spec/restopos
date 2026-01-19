<?php
require_once 'header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $category_id = intval($_POST['category_id']);
            $product_name = escape($_POST['product_name']);
            $description = escape($_POST['description']);
            $cost_price = floatval($_POST['cost_price']);
            $selling_price = floatval($_POST['selling_price']);
            $stock_quantity = intval($_POST['stock_quantity']);
            $min_stock = intval($_POST['min_stock']);
            $unit = escape($_POST['unit']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $_FILES['image']['size'] <= MAX_FILE_SIZE) {
                    $image = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . $image);
                }
            }
            
            if ($action === 'add') {
                $query = "INSERT INTO products (category_id, product_name, description, image, cost_price, selling_price, stock_quantity, min_stock, unit, is_active) 
                         VALUES ($category_id, '$product_name', '$description', '$image', $cost_price, $selling_price, $stock_quantity, $min_stock, '$unit', $is_active)";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Produk berhasil ditambahkan');</script>";
                }
            } else {
                $query = "UPDATE products SET 
                         category_id = $category_id,
                         product_name = '$product_name',
                         description = '$description',
                         cost_price = $cost_price,
                         selling_price = $selling_price,
                         stock_quantity = $stock_quantity,
                         min_stock = $min_stock,
                         unit = '$unit',
                         is_active = $is_active";
                
                if ($image) {
                    $query .= ", image = '$image'";
                }
                
                $query .= " WHERE product_id = $product_id";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Produk berhasil diupdate');</script>";
                }
            }
        } elseif ($action === 'delete') {
            $product_id = intval($_POST['product_id']);
            $conn->query("DELETE FROM products WHERE product_id = $product_id");
            echo "<script>showSuccess('Produk berhasil dihapus');</script>";
        }
    }
}

// Get categories
$query_categories = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name";
$result_categories = $conn->query($query_categories);

// Get products
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

$where = "1=1";
if ($search) {
    $where .= " AND p.product_name LIKE '%$search%'";
}
if ($category_filter) {
    $where .= " AND p.category_id = $category_filter";
}

$query_products = "SELECT p.*, c.category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.category_id 
                  WHERE $where
                  ORDER BY p.product_name";
$result_products = $conn->query($query_products);
?>

<style>
/* Mobile-First Responsive Design */
.filter-section {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    padding: 15px;
    border-radius: 16px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(218, 165, 32, 0.3);
}

.filter-section .form-control,
.filter-section .form-select {
    background: rgba(45, 45, 45, 0.8);
    border: 2px solid rgba(218, 165, 32, 0.3);
    color: #FFD700;
    padding: 10px 12px;
    font-size: 14px;
}

/* Products Grid - Mobile First */
.products-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 5px;
}

.product-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(218, 165, 32, 0.2);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #B8860B, #DAA520, #FFD700);
}

.product-card:active {
    transform: scale(0.98);
}

.product-image-wrapper {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-image-wrapper i {
    font-size: 32px;
    color: #0a0a0a;
}

.product-info {
    text-align: center;
}

.product-name {
    font-weight: 600;
    font-size: 13px;
    color: #FFD700;
    margin-bottom: 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    min-height: 36px;
}

.product-category {
    font-size: 10px;
    color: #DAA520;
    margin-bottom: 5px;
}

.product-price {
    color: #10b981;
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 5px;
}

.product-stock {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
    margin-bottom: 8px;
}

.stock-ok {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.stock-low {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.stock-out {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.product-actions {
    display: flex;
    gap: 5px;
}

.btn-action {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-action i {
    font-size: 14px;
}

.btn-edit {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: rgba(218, 165, 32, 0.6);
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 15px;
}

/* Tablet (3 columns) */
@media (min-width: 576px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    
    .product-name {
        font-size: 14px;
    }
    
    .product-image-wrapper i {
        font-size: 36px;
    }
}

/* Desktop (4 columns) */
@media (min-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .filter-section {
        padding: 20px;
    }
}

/* Large Desktop (5 columns) */
@media (min-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }
    
    .product-card {
        padding: 15px;
    }
    
    .product-name {
        font-size: 15px;
    }
}
</style>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" class="row g-2">
        <div class="col-12 col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?= $search ?>">
        </div>
        <div class="col-6 col-md-3">
            <select name="category" class="form-select">
                <option value="0">Semua Kategori</option>
                <?php 
                $result_categories->data_seek(0);
                while ($cat = $result_categories->fetch_assoc()): 
                ?>
                    <option value="<?= $cat['category_id'] ?>" <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>>
                        <?= $cat['category_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <?php if (isAdmin()): ?>
        <div class="col-12 col-md-3">
            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#productModal">
                <i class="fas fa-plus me-2"></i>Tambah
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Products Grid -->
<div class="products-grid">
    <?php if ($result_products->num_rows > 0): ?>
        <?php while ($product = $result_products->fetch_assoc()): 
            $stock_class = 'stock-ok';
            if ($product['stock_quantity'] <= 0) {
                $stock_class = 'stock-out';
            } elseif ($product['stock_quantity'] <= $product['min_stock']) {
                $stock_class = 'stock-low';
            }
        ?>
            <div class="product-card">
                <div class="product-image-wrapper">
                    <?php if ($product['image'] && file_exists(UPLOAD_PATH . $product['image'])): ?>
                        <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= $product['product_name'] ?>">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <div class="product-category"><?= $product['category_name'] ?></div>
                    <div class="product-name"><?= $product['product_name'] ?></div>
                    <div class="product-price"><?= formatRupiah($product['selling_price']) ?></div>
                    <div class="product-stock <?= $stock_class ?>">
                        Stok: <?= $product['stock_quantity'] ?> <?= $product['unit'] ?>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <div class="product-actions">
                        <button class="btn-action btn-edit" onclick='editProduct(<?= json_encode($product) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteProduct(<?= $product['product_id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>Tidak ada produk</p>
        </div>
    <?php endif; ?>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Produk *</label>
                            <input type="text" name="product_name" id="productName" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori *</label>
                            <select name="category_id" id="categoryId" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $result_categories->data_seek(0);
                                while ($cat = $result_categories->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <label class="form-label">Harga Modal *</label>
                            <input type="number" name="cost_price" id="costPrice" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <label class="form-label">Harga Jual *</label>
                            <input type="number" name="selling_price" id="sellingPrice" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="col-4 mb-3">
                            <label class="form-label">Stok *</label>
                            <input type="number" name="stock_quantity" id="stockQuantity" class="form-control" required>
                        </div>
                        
                        <div class="col-4 mb-3">
                            <label class="form-label">Min Stok *</label>
                            <input type="number" name="min_stock" id="minStock" class="form-control" required>
                        </div>
                        
                        <div class="col-4 mb-3">
                            <label class="form-label">Satuan *</label>
                            <input type="text" name="unit" id="unit" class="form-control" placeholder="pcs" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*">
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('modalTitle').textContent = 'Edit Produk';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('productId').value = product.product_id;
    document.getElementById('productName').value = product.product_name;
    document.getElementById('categoryId').value = product.category_id;
    document.getElementById('description').value = product.description;
    document.getElementById('costPrice').value = product.cost_price;
    document.getElementById('sellingPrice').value = product.selling_price;
    document.getElementById('stockQuantity').value = product.stock_quantity;
    document.getElementById('minStock').value = product.min_stock;
    document.getElementById('unit').value = product.unit;
    document.getElementById('isActive').checked = product.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

async function deleteProduct(id) {
    const confirmed = await confirmDialog(
        'Hapus Produk?',
        'Produk akan dihapus permanen',
        'Ya, Hapus'
    );
    
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('product_id', id);
        
        fetch('products.php', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }
}

document.getElementById('productModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('productForm').reset();
    document.getElementById('modalTitle').textContent = 'Tambah Produk';
    document.getElementById('formAction').value = 'add';
    document.getElementById('productId').value = '';
});
</script>

<?php require_once 'footer.php'; ?>