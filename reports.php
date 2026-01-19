<?php
// =============================================
// EXPORT LOGIC - MUST BE AT THE TOP!
// =============================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once 'config.php';
    
    // Check admin
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isAdmin()) {
        die('Access denied. Admin only.');
    }
    
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    // Set headers for Excel download
    $filename = "Laporan_Transaksi_" . date('Ymd', strtotime($date_from)) . "_to_" . date('Ymd', strtotime($date_to)) . ".xls";
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Add UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
    // Get transactions with items
    $query = "SELECT 
                t.transaction_id,
                t.transaction_code,
                t.transaction_date,
                u.full_name as cashier,
                m.member_name,
                t.subtotal,
                t.discount,
                t.tax,
                t.total_amount,
                t.payment_method
              FROM transactions t
              LEFT JOIN users u ON t.user_id = u.user_id
              LEFT JOIN members m ON t.member_id = m.member_id
              WHERE DATE(t.transaction_date) BETWEEN '$date_from' AND '$date_to'
              AND t.status = 'completed'
              ORDER BY t.transaction_date ASC";
    
    $result = $conn->query($query);
    
    // Start HTML table for Excel
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #DAA520; color: #000; font-weight: bold; }
            .header { background-color: #B8860B; color: #fff; padding: 15px; text-align: center; font-size: 18px; }
            .total-row { background-color: #FFF8DC; font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">LAPORAN TRANSAKSI - SMART RESTO POS</div>
        <p><strong>Periode:</strong> ' . date('d/m/Y', strtotime($date_from)) . ' s/d ' . date('d/m/Y', strtotime($date_to)) . '</p>
        <p><strong>Dicetak:</strong> ' . date('d/m/Y H:i:s') . '</p>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Transaksi</th>
                    <th>Tanggal & Waktu</th>
                    <th>Kasir</th>
                    <th>Member</th>
                    <th>Produk</th>
                    <th>Qty</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal Produk</th>
                    <th>Total Transaksi</th>
                    <th>Diskon</th>
                    <th>Pajak</th>
                    <th>Total Bayar</th>
                    <th>Metode Pembayaran</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    $grand_total = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($trx = $result->fetch_assoc()) {
            $transaction_id = $trx['transaction_id'];
            
            // Get items for this transaction
            $items_query = "SELECT ti.*, p.unit 
                           FROM transaction_items ti 
                           LEFT JOIN products p ON ti.product_id = p.product_id 
                           WHERE ti.transaction_id = $transaction_id";
            $items_result = $conn->query($items_query);
            
            $first_row = true;
            $item_count = $items_result->num_rows;
            
            if ($item_count > 0) {
                while ($item = $items_result->fetch_assoc()) {
                    echo '<tr>';
                    
                    if ($first_row) {
                        echo '<td rowspan="' . $item_count . '" class="text-center">' . $no . '</td>';
                        echo '<td rowspan="' . $item_count . '">' . htmlspecialchars($trx['transaction_code']) . '</td>';
                        echo '<td rowspan="' . $item_count . '">' . date('d/m/Y H:i', strtotime($trx['transaction_date'])) . '</td>';
                        echo '<td rowspan="' . $item_count . '">' . htmlspecialchars($trx['cashier'] ?? '-') . '</td>';
                        echo '<td rowspan="' . $item_count . '">' . htmlspecialchars($trx['member_name'] ?? '-') . '</td>';
                    }
                    
                    echo '<td>' . htmlspecialchars($item['product_name']) . '</td>';
                    echo '<td class="text-right">' . $item['quantity'] . ' ' . ($item['unit'] ?? 'pcs') . '</td>';
                    echo '<td class="text-right">Rp ' . number_format($item['unit_price'], 0, ',', '.') . '</td>';
                    echo '<td class="text-right">Rp ' . number_format($item['subtotal'], 0, ',', '.') . '</td>';
                    
                    if ($first_row) {
                        echo '<td rowspan="' . $item_count . '" class="text-right">Rp ' . number_format($trx['subtotal'], 0, ',', '.') . '</td>';
                        echo '<td rowspan="' . $item_count . '" class="text-right">Rp ' . number_format($trx['discount'], 0, ',', '.') . '</td>';
                        echo '<td rowspan="' . $item_count . '" class="text-right">Rp ' . number_format($trx['tax'], 0, ',', '.') . '</td>';
                        echo '<td rowspan="' . $item_count . '" class="text-right"><strong>Rp ' . number_format($trx['total_amount'], 0, ',', '.') . '</strong></td>';
                        echo '<td rowspan="' . $item_count . '">' . strtoupper($trx['payment_method'] ?? '-') . '</td>';
                        $grand_total += $trx['total_amount'];
                    }
                    
                    echo '</tr>';
                    $first_row = false;
                }
            } else {
                echo '<tr>';
                echo '<td>' . $no . '</td>';
                echo '<td>' . htmlspecialchars($trx['transaction_code']) . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($trx['transaction_date'])) . '</td>';
                echo '<td>' . htmlspecialchars($trx['cashier'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($trx['member_name'] ?? '-') . '</td>';
                echo '<td colspan="4" class="text-center">-</td>';
                echo '<td class="text-right">Rp ' . number_format($trx['subtotal'], 0, ',', '.') . '</td>';
                echo '<td class="text-right">Rp ' . number_format($trx['discount'], 0, ',', '.') . '</td>';
                echo '<td class="text-right">Rp ' . number_format($trx['tax'], 0, ',', '.') . '</td>';
                echo '<td class="text-right"><strong>Rp ' . number_format($trx['total_amount'], 0, ',', '.') . '</strong></td>';
                echo '<td>' . strtoupper($trx['payment_method'] ?? '-') . '</td>';
                echo '</tr>';
                $grand_total += $trx['total_amount'];
            }
            
            $no++;
        }
        
        echo '<tr class="total-row">
                <td colspan="12" class="text-right"><strong>TOTAL KESELURUHAN</strong></td>
                <td class="text-right"><strong>Rp ' . number_format($grand_total, 0, ',', '.') . '</strong></td>
                <td></td>
              </tr>';
    } else {
        echo '<tr><td colspan="14" class="text-center">Tidak ada data transaksi pada periode ini</td></tr>';
    }
    
    echo '</tbody></table>
    <br>
    <p style="font-size: 11px;"><em>Laporan digenerate pada ' . date('d/m/Y H:i:s') . '</em></p>
    </body>
    </html>';
    
    exit;
}

// =============================================
// NORMAL PAGE RENDERING
// =============================================
require_once 'header.php';
checkAdmin();

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$custom_from = isset($_GET['custom_from']) ? $_GET['custom_from'] : date('Y-m-d');
$custom_to = isset($_GET['custom_to']) ? $_GET['custom_to'] : date('Y-m-d');

// Calculate date range
switch ($period) {
    case 'today':
        $date_from = $date_to = date('Y-m-d');
        break;
    case 'yesterday':
        $date_from = $date_to = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to = date('Y-m-d');
        break;
    case 'this_month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
        break;
    case 'last_month':
        $date_from = date('Y-m-01', strtotime('first day of last month'));
        $date_to = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'custom':
        $date_from = $custom_from;
        $date_to = $custom_to;
        break;
    default:
        $date_from = $date_to = date('Y-m-d');
}

// Get revenue data
$query_revenue = "SELECT 
    COALESCE(SUM(total_amount), 0) as total_revenue,
    COALESCE(SUM(subtotal), 0) as gross_revenue,
    COALESCE(SUM(discount), 0) as total_discount,
    COALESCE(SUM(tax), 0) as total_tax,
    COUNT(*) as total_transactions
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to' 
    AND status = 'completed'";
    
$revenue_result = $conn->query($query_revenue);
$revenue = $revenue_result ? $revenue_result->fetch_assoc() : [
    'total_revenue' => 0,
    'gross_revenue' => 0,
    'total_discount' => 0,
    'total_tax' => 0,
    'total_transactions' => 0
];

// Get cost data
$query_cost = "SELECT COALESCE(SUM(ti.quantity * p.cost_price), 0) as total_cost
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.product_id
    JOIN transactions t ON ti.transaction_id = t.transaction_id
    WHERE DATE(t.transaction_date) BETWEEN '$date_from' AND '$date_to'
    AND t.status = 'completed'";
$cost_result = $conn->query($query_cost);
$total_cost = $cost_result ? $cost_result->fetch_assoc()['total_cost'] : 0;

// Get expenses
$query_expenses = "SELECT COALESCE(SUM(amount), 0) as total_expenses
    FROM expenses
    WHERE DATE(expense_date) BETWEEN '$date_from' AND '$date_to'";
$expenses_result = $conn->query($query_expenses);
$total_expenses = $expenses_result ? $expenses_result->fetch_assoc()['total_expenses'] : 0;

// Calculate profit
$gross_profit = ($revenue['gross_revenue'] ?? 0) - $total_cost;
$net_profit = $gross_profit - $total_expenses;

// Get daily sales data for chart
$sales_data = [];
$labels = [];

$start = strtotime($date_from);
$end = strtotime($date_to);

if ($start <= $end) {
    for ($date = $start; $date <= $end; $date = strtotime('+1 day', $date)) {
        $current_date = date('Y-m-d', $date);
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total 
                  FROM transactions 
                  WHERE DATE(transaction_date) = '$current_date' AND status = 'completed'";
        $result = $conn->query($query);
        $total = $result ? $result->fetch_assoc()['total'] : 0;
        
        $labels[] = date('d M', $date);
        $sales_data[] = floatval($total);
    }
} else {
    $labels[] = date('d M', $start);
    $sales_data[] = 0;
}

// Get top products
$query_top_products = "SELECT 
    p.product_name,
    COALESCE(SUM(ti.quantity), 0) as total_qty,
    COALESCE(SUM(ti.subtotal), 0) as total_revenue,
    COALESCE(SUM(ti.quantity * p.cost_price), 0) as total_cost
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.product_id
    JOIN transactions t ON ti.transaction_id = t.transaction_id
    WHERE DATE(t.transaction_date) BETWEEN '$date_from' AND '$date_to'
    AND t.status = 'completed'
    GROUP BY p.product_id
    ORDER BY total_revenue DESC
    LIMIT 10";
$top_products = $conn->query($query_top_products);

// Get payment methods breakdown
$query_payment = "SELECT 
    payment_method,
    COUNT(*) as count,
    COALESCE(SUM(total_amount), 0) as total
    FROM transactions
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'
    AND status = 'completed'
    GROUP BY payment_method";
$payment_methods = $conn->query($query_payment);
?>

<style>
/* Report Header */
.report-header {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
}

.report-header h2 {
    margin: 0 0 10px 0;
    font-size: 24px;
    font-weight: 700;
}

.report-header p {
    margin: 0;
    opacity: 0.9;
}

/* Filter Card */
.filter-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid rgba(218, 165, 32, 0.3);
}

.filter-card .form-control,
.filter-card .form-select {
    background: rgba(45, 45, 45, 0.8);
    border: 1px solid rgba(218, 165, 32, 0.3);
    color: #FFD700;
}

.filter-card .form-label {
    color: #DAA520;
    font-weight: 600;
    font-size: 13px;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.metric-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    padding: 15px;
    border-radius: 12px;
    border: 1px solid rgba(218, 165, 32, 0.2);
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #B8860B, #DAA520, #FFD700);
}

.metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 10px;
}

.icon-revenue { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.icon-profit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.icon-expense { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.icon-net { background: linear-gradient(135deg, #B8860B, #DAA520); color: #0a0a0a; }

.metric-value {
    font-size: 20px;
    font-weight: 700;
    color: #FFD700;
    margin-bottom: 5px;
    word-break: break-word;
}

.metric-label {
    color: #DAA520;
    font-size: 12px;
    text-transform: uppercase;
}

.metric-detail {
    color: rgba(218, 165, 32, 0.7);
    font-size: 11px;
    margin-top: 5px;
}

/* Chart Container */
.chart-container {
    height: 300px;
    position: relative;
}

/* Product Rank */
.product-rank {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(45, 45, 45, 0.6);
    border-radius: 8px;
    margin-bottom: 8px;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.rank-number {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.rank-details {
    flex: 1;
    min-width: 0;
}

.rank-name {
    font-weight: 600;
    color: #FFD700;
    margin-bottom: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rank-stats {
    font-size: 12px;
    color: rgba(218, 165, 32, 0.7);
    word-break: break-word;
}

/* Payment Method Item */
.payment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: rgba(45, 45, 45, 0.6);
    border-radius: 8px;
    margin-bottom: 8px;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.payment-method {
    color: #FFD700;
    font-weight: 600;
}

.payment-count {
    color: rgba(218, 165, 32, 0.7);
    font-size: 12px;
}

.payment-amount {
    color: #DAA520;
    font-weight: 600;
    text-align: right;
}

/* Responsive Design */
@media (max-width: 768px) {
    .report-header {
        padding: 15px;
    }
    
    .report-header h2 {
        font-size: 20px;
    }
    
    .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .metric-card {
        padding: 12px;
    }
    
    .metric-value {
        font-size: 16px;
    }
    
    .metric-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .filter-card {
        padding: 12px;
    }
    
    .filter-card .row .col-md-3 {
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 200px;
    }
}
</style>

<!-- Report Header -->
<div class="report-header">
    <h2><i class="fas fa-chart-line me-2"></i>Laporan Keuangan</h2>
    <p>Periode: <?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?></p>
</div>

<!-- Period Filter -->
<div class="filter-card">
    <form method="GET" class="row g-2">
        <div class="col-md-3 col-sm-6">
            <label class="form-label">Periode</label>
            <select name="period" class="form-select" id="periodSelect">
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="yesterday" <?= $period === 'yesterday' ? 'selected' : '' ?>>Kemarin</option>
                <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Bulan Lalu</option>
                <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-6" id="customDates" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
            <label class="form-label">Dari</label>
            <input type="date" name="custom_from" class="form-control" value="<?= htmlspecialchars($custom_from) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-3 col-sm-6" id="customDatesTo" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
            <label class="form-label">Sampai</label>
            <input type="date" name="custom_to" class="form-control" value="<?= htmlspecialchars($custom_to) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label">&nbsp;</label>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fas fa-filter me-1"></i>Tampilkan
                </button>
                <a href="reports.php?export=excel&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-success flex-fill">
                    <i class="fas fa-file-excel me-1"></i>Export
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Key Metrics -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon icon-revenue">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="metric-value"><?= formatRupiah($revenue['total_revenue']) ?></div>
        <div class="metric-label">Total Pendapatan</div>
        <div class="metric-detail"><?= number_format($revenue['total_transactions']) ?> transaksi</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon icon-profit">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="metric-value"><?= formatRupiah($gross_profit) ?></div>
        <div class="metric-label">Laba Kotor</div>
        <div class="metric-detail">
            <?php 
            $margin = ($revenue['gross_revenue'] > 0) ? ($gross_profit / $revenue['gross_revenue']) * 100 : 0;
            echo 'Margin: ' . number_format($margin, 1) . '%';
            ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon icon-expense">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="metric-value"><?= formatRupiah($total_expenses) ?></div>
        <div class="metric-label">Total Pengeluaran</div>
        <div class="metric-detail">Biaya operasional</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon icon-net">
            <i class="fas fa-sack-dollar"></i>
        </div>
        <div class="metric-value" style="color: <?= $net_profit >= 0 ? '#10b981' : '#ef4444' ?>">
            <?= formatRupiah($net_profit) ?>
        </div>
        <div class="metric-label">Laba Bersih</div>
        <div class="metric-detail">Setelah biaya operasional</div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Grafik Penjualan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Metode Pembayaran</h5>
            </div>
            <div class="card-body">
                <?php if ($payment_methods && $payment_methods->num_rows > 0): ?>
                    <?php while ($pm = $payment_methods->fetch_assoc()): ?>
                        <div class="payment-item">
                            <div>
                                <div class="payment-method"><?= strtoupper($pm['payment_method'] ?? 'TIDAK DIKETAHUI') ?></div>
                                <div class="payment-count"><?= $pm['count'] ?> transaksi</div>
                            </div>
                            <div class="payment-amount"><?= formatRupiah($pm['total']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center mb-0" style="color: rgba(218, 165, 32, 0.6);">Tidak ada data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Produk Terlaris</h5>
    </div>
    <div class="card-body">
        <?php if ($top_products && $top_products->num_rows > 0): ?>
            <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                <?php
                $product_profit = $product['total_revenue'] - $product['total_cost'];
                ?>
                <div class="product-rank">
                    <div class="rank-number"><?= $rank++ ?></div>
                    <div class="rank-details">
                        <div class="rank-name"><?= htmlspecialchars($product['product_name']) ?></div>
                        <div class="rank-stats">
                            Terjual: <?= number_format($product['total_qty']) ?> • 
                            Revenue: <?= formatRupiah($product['total_revenue']) ?> • 
                            Profit: <?= formatRupiah($product_profit) ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center mb-0" style="color: rgba(218, 165, 32, 0.6); padding: 20px 0;">Tidak ada data</p>
        <?php endif; ?>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Period selector
document.getElementById('periodSelect').addEventListener('change', function() {
    const customDates = document.getElementById('customDates');
    const customDatesTo = document.getElementById('customDatesTo');
    
    if (this.value === 'custom') {
        customDates.style.display = 'block';
        customDatesTo.style.display = 'block';
    } else {
        customDates.style.display = 'none';
        customDatesTo.style.display = 'none';
    }
});

// Sales Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart');
    
    if (ctx) {
        const labels = <?= json_encode($labels) ?>;
        const salesData = <?= json_encode($sales_data) ?>;
        
        // Check if we have data
        const hasData = salesData.some(value => value > 0);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Penjualan',
                    data: salesData,
                    borderColor: '#DAA520',
                    backgroundColor: 'rgba(218, 165, 32, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FFD700',
                    pointBorderColor: '#0a0a0a',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0a0a0a',
                        titleColor: '#FFD700',
                        bodyColor: '#DAA520',
                        padding: 10,
                        borderColor: '#DAA520',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#DAA520',
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                                } else if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                }
                                return 'Rp ' + value;
                            }
                        },
                        grid: {
                            color: 'rgba(218, 165, 32, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#DAA520'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>