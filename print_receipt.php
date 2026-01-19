<?php
require_once 'config.php';

$transaction_id = intval($_GET['id'] ?? 0);

if (!$transaction_id) {
    die('Invalid transaction ID');
}

// Get transaction
$query = "SELECT t.*, u.full_name, m.member_name, m.member_code
         FROM transactions t
         LEFT JOIN users u ON t.user_id = u.user_id
         LEFT JOIN members m ON t.member_id = m.member_id
         WHERE t.transaction_id = $transaction_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    die('Transaction not found');
}

$trx = $result->fetch_assoc();

// Get items
$query_items = "SELECT * FROM transaction_items WHERE transaction_id = $transaction_id";
$result_items = $conn->query($query_items);

// Get settings
$query_settings = "SELECT * FROM settings";
$result_settings = $conn->query($query_settings);
$settings = [];
while ($row = $result_settings->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$resto_name = $settings['restaurant_name'] ?? 'Smart Resto POS';
$resto_address = $settings['restaurant_address'] ?? '';
$resto_phone = $settings['restaurant_phone'] ?? '';
$receipt_footer = $settings['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= $trx['transaction_code'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .receipt {
            border: 1px solid #000;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .store-info {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .transaction-info {
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .items {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 10px 0;
            margin-bottom: 10px;
        }
        
        .item {
            margin-bottom: 8px;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .item-detail {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        
        .summary {
            margin-bottom: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .receipt {
                border: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            <i class="fas fa-print"></i> Print Struk
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            Tutup
        </button>
    </div>
    
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="store-name"><?= strtoupper($resto_name) ?></div>
            <?php if ($resto_address): ?>
                <div class="store-info"><?= $resto_address ?></div>
            <?php endif; ?>
            <?php if ($resto_phone): ?>
                <div class="store-info">Telp: <?= $resto_phone ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Transaction Info -->
        <div class="transaction-info">
            <div class="info-row">
                <span>No. Transaksi:</span>
                <strong><?= $trx['transaction_code'] ?></strong>
            </div>
            <div class="info-row">
                <span>Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($trx['transaction_date'])) ?></span>
            </div>
            <div class="info-row">
                <span>Kasir:</span>
                <span><?= $trx['full_name'] ?></span>
            </div>
            <?php if ($trx['member_name']): ?>
            <div class="info-row">
                <span>Member:</span>
                <span><?= $trx['member_name'] ?> (<?= $trx['member_code'] ?>)</span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items -->
        <div class="items">
            <?php while ($item = $result_items->fetch_assoc()): ?>
                <div class="item">
                    <div class="item-name"><?= $item['product_name'] ?></div>
                    <div class="item-detail">
                        <span><?= $item['quantity'] ?> x <?= formatRupiah(floatval($item['unit_price'])) ?></span>
                        <strong><?= formatRupiah(floatval($item['subtotal'])) ?></strong>
                    </div>
                    <?php if ($item['notes']): ?>
                        <div style="font-size: 10px; font-style: italic; margin-top: 2px;">
                            Note: <?= $item['notes'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span><?= formatRupiah(floatval($trx['subtotal'])) ?></span>
            </div>
            <?php if ($trx['discount'] > 0): ?>
            <div class="summary-row">
                <span>Diskon:</span>
                <span>-<?= formatRupiah(floatval($trx['discount'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Pajak:</span>
                <span><?= formatRupiah(floatval($trx['tax'])) ?></span>
            </div>
            <div class="summary-row total">
                <span>TOTAL:</span>
                <span><?= formatRupiah(floatval($trx['total_amount'])) ?></span>
            </div>
            
            <?php if ($trx['payment_method'] === 'cash'): ?>
            <div class="summary-row" style="margin-top: 5px;">
                <span>Bayar:</span>
                <span><?= formatRupiah(floatval($trx['cash_received'])) ?></span>
            </div>
            <div class="summary-row">
                <span>Kembali:</span>
                <span><?= formatRupiah(floatval($trx['change_amount'])) ?></span>
            </div>
            <?php else: ?>
            <div class="summary-row" style="margin-top: 5px;">
                <span>Pembayaran:</span>
                <span><?= strtoupper($trx['payment_method']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($trx['points_earned'] > 0): ?>
            <div class="summary-row" style="margin-top: 5px; font-style: italic;">
                <span>Poin Diperoleh:</span>
                <span>+<?= number_format($trx['points_earned']) ?> poin</span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><?= $receipt_footer ?></p>
            <p style="margin-top: 5px;">Powered by Smart Resto POS</p>
        </div>
    </div>
    
    <script>
        // Auto print on load
        window.onload = function() {
            // Uncomment jika ingin auto print
            // window.print();
        };
    </script>
</body>
</html>