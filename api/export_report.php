<?php
/**
 * Smart Resto POS - Export Financial Report to Excel
 * Admin-only endpoint for downloading financial reports
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isAdmin()) {
    die('Access denied. Admin only.');
}

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    die('Invalid date format');
}

// Set headers for Excel download
$filename = "Laporan_Keuangan_" . date('Ymd', strtotime($start_date)) . "_to_" . date('Ymd', strtotime($end_date)) . ".xls";
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Add UTF-8 BOM for proper Excel encoding
echo "\xEF\xBB\xBF";

// Get transactions data
$query = "SELECT 
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
          WHERE DATE(t.transaction_date) BETWEEN ? AND ?
          AND t.status = 'completed'
          ORDER BY t.transaction_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Summary calculations
$summary_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_sales,
                    SUM(tax) as total_tax,
                    SUM(discount) as total_discount,
                    SUM(subtotal) as gross_sales
                  FROM transactions 
                  WHERE DATE(transaction_date) BETWEEN ? AND ?
                  AND status = 'completed'";

$stmt2 = $conn->prepare($summary_query);
$stmt2->bind_param('ss', $start_date, $end_date);
$stmt2->execute();
$summary_result = $stmt2->get_result();
$summary = $summary_result->fetch_assoc();

// Get HPP (Cost of Goods Sold)
$cost_query = "SELECT SUM(ti.quantity * p.cost_price) as total_cost
               FROM transaction_items ti
               JOIN products p ON ti.product_id = p.product_id
               JOIN transactions t ON ti.transaction_id = t.transaction_id
               WHERE DATE(t.transaction_date) BETWEEN ? AND ?
               AND t.status = 'completed'";

$stmt3 = $conn->prepare($cost_query);
$stmt3->bind_param('ss', $start_date, $end_date);
$stmt3->execute();
$cost_result = $stmt3->get_result();
$cost_data = $cost_result->fetch_assoc();

// Get Expenses
$expense_query = "SELECT SUM(amount) as total_expenses
                  FROM expenses
                  WHERE DATE(expense_date) BETWEEN ? AND ?";

$stmt4 = $conn->prepare($expense_query);
$stmt4->bind_param('ss', $start_date, $end_date);
$stmt4->execute();
$expense_result = $stmt4->get_result();
$expense_data = $expense_result->fetch_assoc();

// Calculate profits
$gross_sales = floatval($summary['gross_sales'] ?? 0);
$total_sales = floatval($summary['total_sales'] ?? 0);
$total_cost = floatval($cost_data['total_cost'] ?? 0);
$total_expenses = floatval($expense_data['total_expenses'] ?? 0);
$gross_profit = $gross_sales - $total_cost;
$net_profit = $gross_profit - $total_expenses;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #DAA520; color: #000; font-weight: bold; }
        .summary { background-color: #FFF8DC; font-weight: bold; }
        .header { background-color: #B8860B; color: #fff; font-weight: bold; padding: 15px; text-align: center; font-size: 18px; }
        .profit { background-color: #d1fae5; }
        .loss { background-color: #fee2e2; }
        .info { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        🏆 LAPORAN KEUANGAN - <?= defined('APP_NAME') ? APP_NAME : 'SMART RESTO POS' ?>
    </div>
    
    <div class="info">
        <p><strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?></p>
        <p><strong>Dicetak:</strong> <?= date('d/m/Y H:i:s') ?></p>
        <p><strong>Oleh:</strong> <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    </div>
    
    <h3>📊 RINGKASAN KEUANGAN</h3>
    <table>
        <tr>
            <th style="width: 40%;">Keterangan</th>
            <th style="width: 30%; text-align: right;">Jumlah</th>
            <th style="width: 30%; text-align: right;">Persentase</th>
        </tr>
        <tr>
            <td>Total Transaksi</td>
            <td style="text-align: right;"><?= number_format($summary['total_transactions'] ?? 0) ?> transaksi</td>
            <td style="text-align: right;">-</td>
        </tr>
        <tr>
            <td>Penjualan Kotor (Gross Sales)</td>
            <td style="text-align: right;">Rp <?= number_format($gross_sales, 0, ',', '.') ?></td>
            <td style="text-align: right;">100%</td>
        </tr>
        <tr>
            <td>Diskon</td>
            <td style="text-align: right;">- Rp <?= number_format($summary['total_discount'] ?? 0, 0, ',', '.') ?></td>
            <td style="text-align: right;"><?= $gross_sales > 0 ? number_format((($summary['total_discount'] ?? 0) / $gross_sales) * 100, 2) : 0 ?>%</td>
        </tr>
        <tr>
            <td>Pajak</td>
            <td style="text-align: right;">Rp <?= number_format($summary['total_tax'] ?? 0, 0, ',', '.') ?></td>
            <td style="text-align: right;"><?= $gross_sales > 0 ? number_format((($summary['total_tax'] ?? 0) / $gross_sales) * 100, 2) : 0 ?>%</td>
        </tr>
        <tr class="summary">
            <td>Penjualan Bersih (Net Sales)</td>
            <td style="text-align: right;">Rp <?= number_format($total_sales, 0, ',', '.') ?></td>
            <td style="text-align: right;">-</td>
        </tr>
        <tr>
            <td>HPP / Cost of Goods Sold</td>
            <td style="text-align: right;">- Rp <?= number_format($total_cost, 0, ',', '.') ?></td>
            <td style="text-align: right;"><?= $total_sales > 0 ? number_format(($total_cost / $total_sales) * 100, 2) : 0 ?>%</td>
        </tr>
        <tr class="summary <?= $gross_profit >= 0 ? 'profit' : 'loss' ?>">
            <td>LABA KOTOR (Gross Profit)</td>
            <td style="text-align: right;">Rp <?= number_format($gross_profit, 0, ',', '.') ?></td>
            <td style="text-align: right;"><?= $total_sales > 0 ? number_format(($gross_profit / $total_sales) * 100, 2) : 0 ?>%</td>
        </tr>
        <tr>
            <td>Biaya Operasional</td>
            <td style="text-align: right;">- Rp <?= number_format($total_expenses, 0, ',', '.') ?></td>
            <td style="text-align: right;"><?= $total_sales > 0 ? number_format(($total_expenses / $total_sales) * 100, 2) : 0 ?>%</td>
        </tr>
        <tr class="summary <?= $net_profit >= 0 ? 'profit' : 'loss' ?>">
            <td><strong>LABA BERSIH (Net Profit)</strong></td>
            <td style="text-align: right;"><strong>Rp <?= number_format($net_profit, 0, ',', '.') ?></strong></td>
            <td style="text-align: right;"><strong><?= $total_sales > 0 ? number_format(($net_profit / $total_sales) * 100, 2) : 0 ?>%</strong></td>
        </tr>
    </table>
    
    <h3>📋 DETAIL TRANSAKSI</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Kode</th>
                <th style="width: 13%;">Tanggal</th>
                <th style="width: 12%;">Kasir</th>
                <th style="width: 12%;">Member</th>
                <th style="width: 12%; text-align: right;">Subtotal</th>
                <th style="width: 10%; text-align: right;">Diskon</th>
                <th style="width: 10%; text-align: right;">Pajak</th>
                <th style="width: 12%; text-align: right;">Total</th>
                <th style="width: 10%;">Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_row_subtotal = 0;
            $total_row_discount = 0;
            $total_row_tax = 0;
            $total_row_amount = 0;
            
            while ($row = $result->fetch_assoc()): 
                $total_row_subtotal += floatval($row['subtotal']);
                $total_row_discount += floatval($row['discount']);
                $total_row_tax += floatval($row['tax']);
                $total_row_amount += floatval($row['total_amount']);
            ?>
            <tr>
                <td style="text-align: center;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['transaction_code']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['transaction_date'])) ?></td>
                <td><?= htmlspecialchars($row['cashier']) ?></td>
                <td><?= htmlspecialchars($row['member_name'] ?? '-') ?></td>
                <td style="text-align: right;">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                <td style="text-align: right;">Rp <?= number_format($row['discount'], 0, ',', '.') ?></td>
                <td style="text-align: right;">Rp <?= number_format($row['tax'], 0, ',', '.') ?></td>
                <td style="text-align: right;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                <td><?= strtoupper($row['payment_method']) ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="summary">
                <td colspan="5" style="text-align: right;"><strong>TOTAL</strong></td>
                <td style="text-align: right;"><strong>Rp <?= number_format($total_row_subtotal, 0, ',', '.') ?></strong></td>
                <td style="text-align: right;"><strong>Rp <?= number_format($total_row_discount, 0, ',', '.') ?></strong></td>
                <td style="text-align: right;"><strong>Rp <?= number_format($total_row_tax, 0, ',', '.') ?></strong></td>
                <td style="text-align: right;"><strong>Rp <?= number_format($total_row_amount, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <br>
    <p style="font-size: 11px; color: #666;">
        <em>Laporan ini digenerate otomatis oleh sistem <?= defined('APP_NAME') ? APP_NAME : 'Smart Resto POS' ?> pada <?= date('d/m/Y H:i:s') ?></em>
    </p>
</body>
</html>

<?php
$stmt->close();
$stmt2->close();
$stmt3->close();
$stmt4->close();
$conn->close();
?>