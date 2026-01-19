<?php
require_once 'header.php';
checkAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
            $category = escape($_POST['expense_category']);
            $amount = floatval($_POST['amount']);
            $description = escape($_POST['description']);
            $expense_date = $_POST['expense_date'];
            $payment_method = $_POST['payment_method'];
            $receipt_number = escape($_POST['receipt_number']);
            $user_id = $_SESSION['user_id'];
            
            if ($action === 'add') {
                $query = "INSERT INTO expenses (expense_category, amount, description, expense_date, payment_method, receipt_number, created_by) 
                         VALUES ('$category', $amount, '$description', '$expense_date', '$payment_method', '$receipt_number', $user_id)";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Pengeluaran berhasil ditambahkan');</script>";
                }
            } else {
                $query = "UPDATE expenses SET 
                         expense_category = '$category',
                         amount = $amount,
                         description = '$description',
                         expense_date = '$expense_date',
                         payment_method = '$payment_method',
                         receipt_number = '$receipt_number'
                         WHERE expense_id = $expense_id";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Pengeluaran berhasil diupdate');</script>";
                }
            }
        } elseif ($action === 'delete') {
            $expense_id = intval($_POST['expense_id']);
            $conn->query("DELETE FROM expenses WHERE expense_id = $expense_id");
            echo "<script>showSuccess('Pengeluaran berhasil dihapus');</script>";
        }
    }
}

// Get date filter
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get expenses
$query_expenses = "SELECT e.*, u.full_name 
                  FROM expenses e
                  JOIN users u ON e.created_by = u.user_id
                  WHERE DATE_FORMAT(e.expense_date, '%Y-%m') = '$month'
                  ORDER BY e.expense_date DESC";
$result_expenses = $conn->query($query_expenses);

// Get summary
$query_summary = "SELECT 
    SUM(amount) as total,
    COUNT(*) as count
    FROM expenses
    WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$month'";
$summary = $conn->query($query_summary)->fetch_assoc();

// Get by category
$query_by_category = "SELECT 
    expense_category,
    SUM(amount) as total,
    COUNT(*) as count
    FROM expenses
    WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$month'
    GROUP BY expense_category
    ORDER BY total DESC";
$by_category = $conn->query($query_by_category);
?>

<style>
/* GOLD THEME - Expenses Page */
.expense-summary {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(218, 165, 32, 0.5);
    position: relative;
    overflow: hidden;
}

.expense-summary::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.2) 0%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}

.expense-summary h3 {
    position: relative;
    z-index: 1;
    margin-bottom: 15px;
    font-weight: 700;
}

.summary-value {
    font-size: 48px;
    font-weight: 700;
    margin: 10px 0;
    position: relative;
    z-index: 1;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.expense-summary p {
    position: relative;
    z-index: 1;
    margin: 0;
    opacity: 0.9;
}

/* Filter Card */
.filter-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(218, 165, 32, 0.3);
}

.filter-card .form-control,
.filter-card .form-select {
    background: rgba(45, 45, 45, 0.8);
    border: 2px solid rgba(218, 165, 32, 0.3);
    color: #FFD700;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    background: rgba(45, 45, 45, 0.95);
    border-color: #DAA520;
    color: #FFD700;
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.2);
}

/* Category Items */
.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px;
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.8), rgba(45, 45, 45, 0.8));
    border-left: 4px solid #DAA520;
    border-radius: 10px;
    margin-bottom: 12px;
    transition: all 0.3s;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.category-item:hover {
    background: linear-gradient(135deg, rgba(45, 45, 45, 0.9), rgba(26, 26, 26, 0.9));
    transform: translateX(8px);
    border-color: rgba(218, 165, 32, 0.5);
    box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
}

.category-name {
    font-weight: 700;
    color: #FFD700;
    font-size: 15px;
}

.category-amount {
    font-size: 18px;
    font-weight: 700;
    color: #DAA520;
}

.category-count {
    font-size: 12px;
    color: rgba(218, 165, 32, 0.7);
    margin-top: 3px;
}

/* Expenses Table */
.table-container {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(218, 165, 32, 0.3);
}

.table {
    margin: 0;
    color: #0a0a0a;
}

.table thead th {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    border: none;
    padding: 15px 12px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(218, 165, 32, 0.2);
}

.table tbody tr:hover {
    background: rgba(218, 165, 32, 0.1);
}

/* Badges */
.badge-category {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.badge-method {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.method-cash {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.method-transfer {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.method-debit {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.method-credit {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: rgba(218, 165, 32, 0.6);
}

.empty-state i {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .summary-value {
        font-size: 36px;
    }
    
    .expense-summary {
        padding: 20px;
    }
    
    .category-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        font-size: 12px;
    }
    
    .table thead th,
    .table tbody td {
        padding: 8px 6px;
    }
}
</style>

<!-- Summary -->
<div class="expense-summary">
    <h3><i class="fas fa-file-invoice-dollar me-3"></i>Pengeluaran Bulan Ini</h3>
    <div class="summary-value"><?= formatRupiah($summary['total'] ?? 0) ?></div>
    <p><?= number_format($summary['count'] ?? 0) ?> transaksi pengeluaran</p>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <!-- Filter & Add -->
        <div class="filter-card">
            <div class="row g-3">
                <div class="col-md-6">
                    <form method="GET">
                        <div class="input-group">
                            <input type="month" name="month" class="form-control" value="<?= $month ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#expenseModal">
                        <i class="fas fa-plus me-2"></i>Tambah Pengeluaran
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Expenses Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_expenses->num_rows > 0): ?>
                            <?php while ($expense = $result_expenses->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <small><?= formatDate($expense['expense_date']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-category"><?= $expense['expense_category'] ?></span>
                                    </td>
                                    <td>
                                        <?= $expense['description'] ?>
                                        <?php if ($expense['receipt_number']): ?>
                                            <br><small class="text-muted">No: <?= $expense['receipt_number'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= formatRupiah($expense['amount']) ?></strong></td>
                                    <td>
                                        <span class="badge-method method-<?= $expense['payment_method'] ?>">
                                            <?= strtoupper($expense['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editExpense(<?= json_encode($expense) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteExpense(<?= $expense['expense_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>Tidak ada pengeluaran</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <!-- By Category -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie me-2"></i>Per Kategori</h5>
            </div>
            <div class="card-body">
                <?php if ($by_category->num_rows > 0): ?>
                    <?php while ($cat = $by_category->fetch_assoc()): ?>
                        <div class="category-item">
                            <div>
                                <div class="category-name"><?= $cat['expense_category'] ?></div>
                                <div class="category-count"><?= $cat['count'] ?> transaksi</div>
                            </div>
                            <div class="category-amount"><?= formatRupiah($cat['total']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center" style="color: rgba(218, 165, 32, 0.6); padding: 40px;">Tidak ada data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="expenseForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="expense_id" id="expenseId">
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <input type="text" name="expense_category" id="category" class="form-control" 
                               list="categoryList" required>
                        <datalist id="categoryList">
                            <option value="Gaji Karyawan">
                            <option value="Sewa Tempat">
                            <option value="Listrik & Air">
                            <option value="Internet & Telpon">
                            <option value="Bahan Baku">
                            <option value="Perlengkapan">
                            <option value="Transportasi">
                            <option value="Perawatan & Perbaikan">
                            <option value="Marketing & Promosi">
                            <option value="Lain-lain">
                        </datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="expense_date" id="expenseDate" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran *</label>
                        <select name="payment_method" id="paymentMethod" class="form-select" required>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nomor Bukti</label>
                        <input type="text" name="receipt_number" id="receiptNumber" class="form-control" 
                               placeholder="Opsional">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi *</label>
                        <textarea name="description" id="description" class="form-control" rows="3" 
                                  placeholder="Deskripsi pengeluaran" required></textarea>
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
function editExpense(expense) {
    document.getElementById('modalTitle').textContent = 'Edit Pengeluaran';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('expenseId').value = expense.expense_id;
    document.getElementById('category').value = expense.expense_category;
    document.getElementById('amount').value = expense.amount;
    document.getElementById('expenseDate').value = expense.expense_date;
    document.getElementById('paymentMethod').value = expense.payment_method;
    document.getElementById('receiptNumber').value = expense.receipt_number || '';
    document.getElementById('description').value = expense.description;
    
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}

async function deleteExpense(id) {
    const confirmed = await confirmDialog(
        'Hapus Pengeluaran?',
        'Data akan dihapus permanen. Lanjutkan?',
        'Ya, Hapus'
    );
    
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('expense_id', id);
        
        fetch('expenses.php', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }
}

// Reset form
document.getElementById('expenseModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('expenseForm').reset();
    document.getElementById('modalTitle').textContent = 'Tambah Pengeluaran';
    document.getElementById('formAction').value = 'add';
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseDate').value = '<?= date('Y-m-d') ?>';
});
</script>

<?php require_once 'footer.php'; ?>