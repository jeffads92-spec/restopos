<?php
require_once 'header.php';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $product_id = intval($_POST['product_id']);
    $type = $_POST['type']; // 'in' or 'out' or 'adjustment'
    $quantity = intval($_POST['quantity']);
    $notes = escape($_POST['notes']);
    $user_id = $_SESSION['user_id'];
    
    // Get current stock
    $query = "SELECT stock_quantity FROM products WHERE product_id = $product_id";
    $result = $conn->query($query);
    $current_stock = $result->fetch_assoc()['stock_quantity'];
    
    // Calculate new stock
    if ($type === 'in') {
        $new_stock = $current_stock + $quantity;
    } elseif ($type === 'out') {
        $new_stock = $current_stock - $quantity;
    } else {
        $new_stock = $quantity; // adjustment = set to specific value
    }
    
    if ($new_stock < 0) {
        echo "<script>showError('Stok tidak boleh negatif!');</script>";
    } else {
        // Update stock
        $conn->query("UPDATE products SET stock_quantity = $new_stock WHERE product_id = $product_id");
        
        // Insert history
        $stmt = $conn->prepare("INSERT INTO stock_history (product_id, type, quantity, stock_before, stock_after, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiissi", $product_id, $type, $quantity, $current_stock, $new_stock, $notes, $user_id);
        $stmt->execute();
        
        echo "<script>showSuccess('Stok berhasil diupdate!');</script>";
    }
}

// Get products with low stock
$query_low_stock = "SELECT * FROM products WHERE stock_quantity <= min_stock AND is_active = 1 ORDER BY stock_quantity ASC";
$result_low_stock = $conn->query($query_low_stock);

// Get all products for stock management
$query_products = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_name";
$result_products = $conn->query($query_products);

// Get recent stock history
$query_history = "SELECT sh.*, p.product_name, u.full_name 
                 FROM stock_history sh 
                 JOIN products p ON sh.product_id = p.product_id 
                 JOIN users u ON sh.created_by = u.user_id 
                 ORDER BY sh.created_at DESC 
                 LIMIT 20";
$result_history = $conn->query($query_history);
?>

<style>
    .stock-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .stock-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .stock-info h6 {
        margin: 0 0 5px 0;
        color: #92400e;
        font-weight: 600;
    }
    
    .stock-numbers {
        font-size: 13px;
        color: #b45309;
    }
    
    .history-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-in {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-out {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-adjustment {
        background: #dbeafe;
        color: #1e40af;
    }
</style>

<div class="row">
    <!-- Low Stock Alert -->
    <div class="col-md-4 mb-4">
        <div class="stock-card">
            <h5 class="mb-3">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Stok Menipis
            </h5>
            
            <?php if ($result_low_stock->num_rows > 0): ?>
                <?php while ($item = $result_low_stock->fetch_assoc()): ?>
                    <div class="stock-item">
                        <div class="stock-info">
                            <h6><?= $item['product_name'] ?></h6>
                            <div class="stock-numbers">
                                Stok: <?= $item['stock_quantity'] ?> / Min: <?= $item['min_stock'] ?> <?= $item['unit'] ?>
                            </div>
                        </div>
                        <?php if (isAdmin()): ?>
                        <button class="btn btn-sm btn-warning" onclick='adjustStock(<?= json_encode($item) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                    Semua stok aman
                </p>
            <?php endif; ?>
        </div>
        
        <?php if (isAdmin()): ?>
        <div class="stock-card">
            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#stockModal">
                <i class="fas fa-plus me-2"></i>Update Stok
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Stock History -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Riwayat Pergerakan Stok</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>Stok</th>
                                <th>Catatan</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_history->num_rows > 0): ?>
                                <?php while ($history = $result_history->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <small><?= formatDateTime($history['created_at']) ?></small>
                                        </td>
                                        <td><strong><?= $history['product_name'] ?></strong></td>
                                        <td>
                                            <span class="history-badge badge-<?= $history['type'] ?>">
                                                <?php
                                                    if ($history['type'] === 'in') echo 'Masuk';
                                                    elseif ($history['type'] === 'out') echo 'Keluar';
                                                    else echo 'Adjustment';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                <?= $history['type'] === 'out' ? '-' : '+' ?><?= $history['quantity'] ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= $history['stock_before'] ?></small>
                                            <i class="fas fa-arrow-right mx-1"></i>
                                            <strong><?= $history['stock_after'] ?></strong>
                                        </td>
                                        <td><small><?= $history['notes'] ?></small></td>
                                        <td><small><?= $history['full_name'] ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada riwayat
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Produk *</label>
                        <select name="product_id" id="productSelect" class="form-select" required>
                            <option value="">Pilih Produk</option>
                            <?php while ($product = $result_products->fetch_assoc()): ?>
                                <option value="<?= $product['product_id'] ?>" data-stock="<?= $product['stock_quantity'] ?>">
                                    <?= $product['product_name'] ?> (Stok: <?= $product['stock_quantity'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe *</label>
                        <select name="type" class="form-select" required>
                            <option value="in">Stok Masuk</option>
                            <option value="out">Stok Keluar</option>
                            <option value="adjustment">Adjustment (Set Manual)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Stok Saat Ini:</strong> <span id="currentStock">-</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('productSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const stock = option.getAttribute('data-stock');
    document.getElementById('currentStock').textContent = stock || '-';
});

function adjustStock(product) {
    document.getElementById('productSelect').value = product.product_id;
    document.getElementById('currentStock').textContent = product.stock_quantity;
    new bootstrap.Modal(document.getElementById('stockModal')).show();
}
</script>

<?php require_once 'footer.php'; ?>