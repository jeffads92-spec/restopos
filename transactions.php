<?php
require_once 'header.php';

// Handle cancel/delete transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $transaction_id = intval($_POST['transaction_id']);
        
        if ($action === 'cancel') {
            $conn->query("UPDATE transactions SET status = 'cancelled' WHERE transaction_id = $transaction_id");
            
            $items = $conn->query("SELECT product_id, quantity FROM transaction_items WHERE transaction_id = $transaction_id");
            while ($item = $items->fetch_assoc()) {
                $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE product_id = {$item['product_id']}");
            }
            
            echo "<script>showSuccess('Transaksi berhasil dibatalkan dan stok dikembalikan');</script>";
        } elseif ($action === 'delete') {
            $conn->begin_transaction();
            try {
                $items = $conn->query("SELECT product_id, quantity FROM transaction_items WHERE transaction_id = $transaction_id");
                while ($item = $items->fetch_assoc()) {
                    $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE product_id = {$item['product_id']}");
                }
                
                $conn->query("DELETE FROM transaction_items WHERE transaction_id = $transaction_id");
                $conn->query("DELETE FROM stock_history WHERE transaction_id = $transaction_id");
                $conn->query("DELETE FROM transactions WHERE transaction_id = $transaction_id");
                
                $conn->commit();
                echo "<script>showSuccess('Transaksi berhasil dihapus dan stok dikembalikan');</script>";
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>showError('Gagal menghapus transaksi');</script>";
            }
        }
    }
}

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build where clause
$where = "WHERE DATE(t.transaction_date) BETWEEN '$date_from' AND '$date_to'";
if ($payment_method) {
    $where .= " AND t.payment_method = '$payment_method'";
}
if ($status_filter) {
    $where .= " AND t.status = '$status_filter'";
}

// Get transactions
$query_transactions = "SELECT t.*, u.full_name, m.member_name
                      FROM transactions t
                      LEFT JOIN users u ON t.user_id = u.user_id
                      LEFT JOIN members m ON t.member_id = m.member_id
                      $where
                      ORDER BY t.transaction_date DESC";
$result_transactions = $conn->query($query_transactions);

// Get summary - FIXED: Use COALESCE to convert NULL to 0
$query_summary = "SELECT 
    COUNT(*) as total_transactions,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN discount ELSE 0 END), 0) as total_discount,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN tax ELSE 0 END), 0) as total_tax,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
    FROM transactions t $where";
$summary = $conn->query($query_summary)->fetch_assoc();
?>

<style>
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: linear-gradient(135deg, var(--black), var(--gray-dark));
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    border: 2px solid var(--gold-dark);
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(218,165,32,0.2) 0%, transparent 70%);
}

.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 15px;
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: var(--black);
}

.summary-value {
    font-size: 26px;
    font-weight: 700;
    color: var(--gold-light);
    margin-bottom: 5px;
}

.summary-label {
    color: var(--gold);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.payment-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-cash { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.badge-qris { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.badge-transfer { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.badge-debit { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.badge-credit { background: linear-gradient(135deg, #ec4899, #db2777); color: white; }

.status-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-pending { background: #fef3c7; color: #92400e; }

.btn-action {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-card {
    background: white;
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    border: 2px solid var(--gold-lighter);
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .btn-action {
        padding: 4px 8px;
        font-size: 11px;
    }
}
</style>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="summary-value"><?= number_format(intval($summary['total_transactions'] ?? 0)) ?></div>
        <div class="summary-label">Total Transaksi</div>
    </div>
    
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="summary-value"><?= formatRupiah(floatval($summary['total_revenue'] ?? 0)) ?></div>
        <div class="summary-label">Total Pendapatan</div>
    </div>
    
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-tag"></i>
        </div>
        <div class="summary-value"><?= formatRupiah(floatval($summary['total_discount'] ?? 0)) ?></div>
        <div class="summary-label">Total Diskon</div>
    </div>
    
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-ban"></i>
        </div>
        <div class="summary-value"><?= number_format(intval($summary['cancelled_count'] ?? 0)) ?></div>
        <div class="summary-label">Dibatalkan</div>
    </div>
</div>

<!-- Filter -->
<div class="filter-card">
    <form method="GET" class="row g-3">
        <div class="col-md-2 col-6">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">Pembayaran</label>
            <select name="payment_method" class="form-select">
                <option value="">Semua</option>
                <option value="cash" <?= $payment_method === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="qris" <?= $payment_method === 'qris' ? 'selected' : '' ?>>QRIS</option>
                <option value="transfer" <?= $payment_method === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                <option value="debit" <?= $payment_method === 'debit' ? 'selected' : '' ?>>Debit</option>
                <option value="credit" <?= $payment_method === 'credit' ? 'selected' : '' ?>>Credit</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Selesai</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-success w-100" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>Export
            </button>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5><i class="fas fa-list me-2"></i>Daftar Transaksi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal & Waktu</th>
                        <th>Kasir</th>
                        <th>Member</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_transactions && $result_transactions->num_rows > 0): ?>
                        <?php while ($trx = $result_transactions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($trx['transaction_code']) ?></strong>
                                </td>
                                <td>
                                    <small><?= formatDateTime($trx['transaction_date']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($trx['full_name']) ?></td>
                                <td>
                                    <?php if ($trx['member_name']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($trx['member_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="payment-badge badge-<?= $trx['payment_method'] ?>">
                                        <?= strtoupper($trx['payment_method']) ?>
                                    </span>
                                </td>
                                <td><strong><?= formatRupiah(floatval($trx['total_amount'] ?? 0)) ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?= $trx['status'] ?>">
                                        <?= ucfirst($trx['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button class="btn btn-info btn-action" onclick="viewDetail(<?= $trx['transaction_id'] ?>)" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="print_receipt.php?id=<?= $trx['transaction_id'] ?>" target="_blank" class="btn btn-success btn-action" title="Print">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if (isAdmin() && $trx['status'] === 'completed'): ?>
                                            <button class="btn btn-warning btn-action" onclick="cancelTransaction(<?= $trx['transaction_id'] ?>)" title="Batal">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <button class="btn btn-danger btn-action" onclick="deleteTransaction(<?= $trx['transaction_id'] ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Tidak ada transaksi</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function viewDetail(transactionId) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    
    try {
        const response = await fetch(`api/get_transaction_detail.php?id=${transactionId}`);
        const data = await response.json();
        
        if (data.success) {
            const trx = data.transaction;
            const items = data.items;
            
            let html = `
                <div class="mb-4">
                    <h6 class="border-bottom pb-2">Informasi Transaksi</h6>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Kode:</strong> ${trx.transaction_code}</p>
                            <p><strong>Tanggal:</strong> ${trx.transaction_date}</p>
                            <p><strong>Kasir:</strong> ${trx.full_name}</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Member:</strong> ${trx.member_name || '-'}</p>
                            <p><strong>Metode:</strong> ${trx.payment_method.toUpperCase()}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${trx.status === 'completed' ? 'success' : 'danger'}">${trx.status}</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h6 class="border-bottom pb-2">Items</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            items.forEach(item => {
                html += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>${formatRupiah(parseFloat(item.unit_price))}</td>
                        <td>${formatRupiah(parseFloat(item.subtotal))}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div>
                    <h6 class="border-bottom pb-2">Ringkasan</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong>${formatRupiah(parseFloat(trx.subtotal))}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Diskon:</span>
                        <strong class="text-danger">-${formatRupiah(parseFloat(trx.discount))}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pajak:</span>
                        <strong>${formatRupiah(parseFloat(trx.tax))}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span><strong>Total:</strong></span>
                        <strong class="text-primary fs-5">${formatRupiah(parseFloat(trx.total_amount))}</strong>
                    </div>
            `;
            
            if (trx.payment_method === 'cash') {
                html += `
                    <div class="d-flex justify-content-between mt-2">
                        <span>Uang Diterima:</span>
                        <strong>${formatRupiah(parseFloat(trx.cash_received))}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Kembalian:</span>
                        <strong class="text-success">${formatRupiah(parseFloat(trx.change_amount))}</strong>
                    </div>
                `;
            }
            
            html += '</div>';
            
            document.getElementById('detailContent').innerHTML = html;
        }
    } catch (error) {
        document.getElementById('detailContent').innerHTML = '<p class="text-danger">Error loading detail</p>';
    }
}

async function cancelTransaction(id) {
    const confirmed = await confirmDialog(
        'Batalkan Transaksi?',
        'Stok akan dikembalikan. Lanjutkan?',
        'Ya, Batalkan'
    );
    
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('transaction_id', id);
        
        fetch('transactions.php', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }
}

async function deleteTransaction(id) {
    const confirmed = await confirmDialog(
        'Hapus Transaksi?',
        'Transaksi akan dihapus permanen dan stok dikembalikan!',
        'Ya, Hapus'
    );
    
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('transaction_id', id);
        
        fetch('transactions.php', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }
}

function exportToExcel() {
    const table = document.getElementById('transactionsTable');
    const html = table.outerHTML;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'transactions_' + new Date().toISOString().slice(0, 10) + '.xls';
    a.click();
}
</script>

<?php require_once 'footer.php'; ?>