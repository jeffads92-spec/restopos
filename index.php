<?php
require_once 'config.php';
checkLogin();

// Get statistics
$today = date('Y-m-d');
$this_month = date('Y-m');

// Total transactions today
$query_today = "SELECT COUNT(*) as total, SUM(total_amount) as revenue 
                FROM transactions 
                WHERE DATE(transaction_date) = '$today' AND status = 'completed'";
$result_today = $conn->query($query_today);
$today_data = $result_today->fetch_assoc();

// Total transactions this month
$query_month = "SELECT COUNT(*) as total, SUM(total_amount) as revenue, SUM(subtotal - (SELECT COALESCE(SUM(ti.quantity * p.cost_price), 0) FROM transaction_items ti JOIN products p ON ti.product_id = p.product_id WHERE ti.transaction_id = transactions.transaction_id)) as profit
                FROM transactions 
                WHERE DATE_FORMAT(transaction_date, '%Y-%m') = '$this_month' AND status = 'completed'";
$result_month = $conn->query($query_month);
$month_data = $result_month->fetch_assoc();

// Total products
$query_products = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
$result_products = $conn->query($query_products);
$total_products = $result_products->fetch_assoc()['total'];

// Low stock products
$query_low_stock = "SELECT COUNT(*) as total FROM products WHERE stock_quantity <= min_stock AND is_active = 1";
$result_low_stock = $conn->query($query_low_stock);
$low_stock_count = $result_low_stock->fetch_assoc()['total'];

// Total members
$query_members = "SELECT COUNT(*) as total FROM members WHERE is_active = 1";
$result_members = $conn->query($query_members);
$total_members = $result_members->fetch_assoc()['total'];

// Sales chart data (last 7 days)
$sales_data = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query_sales = "SELECT COALESCE(SUM(total_amount), 0) as total 
                    FROM transactions 
                    WHERE DATE(transaction_date) = '$date' AND status = 'completed'";
    $result_sales = $conn->query($query_sales);
    $sales = $result_sales->fetch_assoc()['total'];
    $sales_data[] = floatval($sales);
    $labels[] = date('d M', strtotime($date));
}

// Top selling products
$query_top_products = "SELECT p.product_name, SUM(ti.quantity) as total_sold, SUM(ti.subtotal) as total_revenue
                       FROM transaction_items ti
                       JOIN products p ON ti.product_id = p.product_id
                       JOIN transactions t ON ti.transaction_id = t.transaction_id
                       WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = '$this_month' AND t.status = 'completed'
                       GROUP BY p.product_id
                       ORDER BY total_sold DESC
                       LIMIT 5";
$result_top_products = $conn->query($query_top_products);

// Recent transactions
$query_recent = "SELECT t.*, u.full_name, m.member_name
                 FROM transactions t
                 LEFT JOIN users u ON t.user_id = u.user_id
                 LEFT JOIN members m ON t.member_id = m.member_id
                 WHERE t.status = 'completed'
                 ORDER BY t.transaction_date DESC
                 LIMIT 5";
$result_recent = $conn->query($query_recent);

// Low stock alert
$query_low_stock_list = "SELECT product_name, stock_quantity, min_stock
                         FROM products
                         WHERE stock_quantity <= min_stock AND is_active = 1
                         ORDER BY stock_quantity ASC
                         LIMIT 5";
$result_low_stock_list = $conn->query($query_low_stock_list);

require_once 'header.php';
?>

<style>
/* Dashboard Styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stats-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    border-radius: 16px;
    padding: 25px;
    position: relative;
    overflow: hidden;
    border: 2px solid rgba(218, 165, 32, 0.3);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(218, 165, 32, 0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.stats-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(218, 165, 32, 0.4);
    border-color: rgba(218, 165, 32, 0.6);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}

.stats-primary {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    box-shadow: 0 8px 20px rgba(218, 165, 32, 0.4);
}

.stats-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.stats-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.stats-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.stats-label {
    font-size: 12px;
    color: #DAA520;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.stats-value {
    font-size: 32px;
    font-weight: 700;
    color: #FFD700;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
    font-family: 'Inter', sans-serif;
}

.stats-info-text {
    font-size: 12px;
    color: rgba(218, 165, 32, 0.7);
    position: relative;
    z-index: 1;
}

.chart-container {
    position: relative;
    height: 300px;
    background: rgba(26, 26, 26, 0.5);
    border-radius: 12px;
    padding: 15px;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.8), rgba(45, 45, 45, 0.8));
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.product-item:hover {
    background: linear-gradient(135deg, rgba(45, 45, 45, 0.9), rgba(26, 26, 26, 0.9));
    transform: translateX(8px);
    border-color: rgba(218, 165, 32, 0.5);
}

.product-rank {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #0a0a0a;
    margin-right: 12px;
    font-size: 14px;
}

.product-name {
    font-weight: 600;
    color: #FFD700;
    flex: 1;
    font-size: 14px;
}

.product-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.product-qty {
    color: #10b981;
    font-weight: 600;
}

.product-revenue {
    color: #DAA520;
    font-weight: 600;
}

.alert-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15));
    border-left: 3px solid #f59e0b;
    border-radius: 8px;
    margin-bottom: 10px;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.alert-product {
    font-weight: 600;
    color: #fbbf24;
    font-size: 13px;
}

.alert-stock {
    font-size: 11px;
    color: #fcd34d;
}

.transaction-item {
    padding: 12px;
    background: rgba(26, 26, 26, 0.6);
    border-radius: 8px;
    margin-bottom: 8px;
    border: 1px solid rgba(218, 165, 32, 0.2);
    transition: all 0.3s;
}

.transaction-item:hover {
    background: rgba(45, 45, 45, 0.8);
    border-color: rgba(218, 165, 32, 0.4);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .stats-card {
        padding: 15px;
    }
    
    .stats-icon {
        width: 45px;
        height: 45px;
        font-size: 22px;
    }
    
    .stats-value {
        font-size: 24px;
    }
    
    .product-stats {
        flex-direction: column;
        gap: 5px;
    }
    
    .chart-container {
        height: 250px;
    }
}
</style>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stats-card">
        <div class="stats-icon stats-primary">
            <i class="fas fa-cash-register"></i>
        </div>
        <div class="stats-label">Transaksi Hari Ini</div>
        <div class="stats-value"><?= number_format($today_data['total'] ?? 0) ?></div>
        <div class="stats-info-text"><?= formatRupiah($today_data['revenue'] ?? 0) ?></div>
    </div>
    
    <div class="stats-card">
        <div class="stats-icon stats-success">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stats-label">Pendapatan Bulan Ini</div>
        <div class="stats-value"><?= formatRupiah($month_data['revenue'] ?? 0) ?></div>
        <div class="stats-info-text"><?= number_format($month_data['total'] ?? 0) ?> transaksi</div>
    </div>
    
    <div class="stats-card">
        <div class="stats-icon stats-info">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stats-label">Produk Aktif</div>
        <div class="stats-value"><?= number_format($total_products) ?></div>
        <div class="stats-info-text">Dalam inventori</div>
    </div>
    
    <div class="stats-card">
        <div class="stats-icon stats-warning">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stats-label">Member VIP</div>
        <div class="stats-value"><?= number_format($total_members) ?></div>
        <div class="stats-info-text">Member aktif</div>
    </div>
</div>

<!-- Charts and Lists -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-area me-2"></i>Grafik Penjualan - 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-fire me-2"></i>Produk Terlaris</h5>
            </div>
            <div class="card-body">
                <?php if ($result_top_products && $result_top_products->num_rows > 0): ?>
                    <?php $rank = 1; while ($product = $result_top_products->fetch_assoc()): ?>
                        <div class="product-item">
                            <div class="product-rank"><?= $rank++ ?></div>
                            <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                            <div class="product-stats">
                                <span class="product-qty">
                                    <i class="fas fa-shopping-cart me-1"></i><?= number_format($product['total_sold']) ?> terjual
                                </span>
                                <span class="product-revenue">
                                    <i class="fas fa-coins me-1"></i><?= formatRupiah($product['total_revenue']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-4">Belum ada data penjualan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <?php if ($low_stock_count > 0): ?>
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Stok Menipis</h5>
            </div>
            <div class="card-body">
                <?php if ($result_low_stock_list && $result_low_stock_list->num_rows > 0): ?>
                    <?php while ($item = $result_low_stock_list->fetch_assoc()): ?>
                        <div class="alert-item">
                            <div>
                                <div class="alert-product"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div class="alert-stock">
                                    Stok: <?= $item['stock_quantity'] ?> / Min: <?= $item['min_stock'] ?>
                                </div>
                            </div>
                            <i class="fas fa-exclamation-circle fa-2x" style="color: #f59e0b;"></i>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock-rotate-left me-2"></i>Transaksi Terbaru</h5>
            </div>
            <div class="card-body">
                <?php if ($result_recent && $result_recent->num_rows > 0): ?>
                    <?php while ($trx = $result_recent->fetch_assoc()): ?>
                        <div class="transaction-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong style="color: #FFD700;"><?= htmlspecialchars($trx['transaction_code']) ?></strong>
                                    <br>
                                    <small style="color: #DAA520;"><?= formatDateTime($trx['transaction_date']) ?></small>
                                </div>
                                <strong style="color: #10b981;"><?= formatRupiah($trx['total_amount']) ?></strong>
                            </div>
                            <?php if ($trx['member_name']): ?>
                                <span class="badge" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <i class="fas fa-user-shield me-1"></i><?= htmlspecialchars($trx['member_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                    <a href="transactions.php" class="btn btn-primary w-100 mt-3">
                        Lihat Semua <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                <?php else: ?>
                    <p class="text-center text-muted py-4">Belum ada transaksi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart');
    
    if (ctx) {
        const labels = <?= json_encode($labels) ?>;
        const salesData = <?= json_encode($sales_data) ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Penjualan',
                    data: salesData,
                    borderColor: '#DAA520',
                    backgroundColor: 'rgba(218, 165, 32, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FFD700',
                    pointBorderColor: '#0a0a0a',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
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
                        padding: 12,
                        borderColor: '#DAA520',
                        borderWidth: 2,
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