<?php
require_once 'config.php';
checkLogin();

// Get pending and preparing orders
$query_orders = "SELECT 
    ti.item_id,
    ti.transaction_id,
    ti.product_name,
    ti.quantity,
    ti.notes,
    ti.status,
    ti.created_at,
    t.transaction_code,
    t.transaction_date
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id = t.transaction_id
WHERE ti.status IN ('pending', 'preparing', 'ready')
ORDER BY ti.created_at ASC";

$result_orders = $conn->query($query_orders);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Kitchen Display - <?= APP_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --gold-dark: #B8860B;
            --gold: #DAA520;
            --gold-light: #FFD700;
            --gold-lighter: #FFF8DC;
            --black: #0a0a0a;
            --gray-dark: #1a1a1a;
            --gray: #2d2d2d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--black) 0%, var(--gray-dark) 100%);
            color: white;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(218, 165, 32, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.06) 0%, transparent 50%);
            animation: backgroundFloat 30s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes backgroundFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .kitchen-header {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--black);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 
                0 10px 40px rgba(218, 165, 32, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        
        .kitchen-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.2) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        
        .kitchen-header h1 {
            margin: 0 0 10px 0;
            font-size: 36px;
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .kitchen-header p {
            margin: 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .clock {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .status-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .status-tab {
            padding: 15px 30px;
            background: rgba(45, 45, 45, 0.8);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 12px;
            color: var(--gold-light);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .status-tab:hover {
            border-color: var(--gold);
            background: rgba(218, 165, 32, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(218, 165, 32, 0.3);
        }
        
        .status-tab.active {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--black);
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.5);
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .order-card {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-light));
        }
        
        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 50px rgba(218, 165, 32, 0.4);
        }
        
        .order-card.pending {
            border-color: #f59e0b;
        }
        
        .order-card.preparing {
            border-color: #3b82f6;
        }
        
        .order-card.ready {
            border-color: #10b981;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(218, 165, 32, 0.2);
        }
        
        .order-code {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .order-time {
            background: rgba(218, 165, 32, 0.2);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            color: var(--gold-light);
            border: 1px solid var(--gold-dark);
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(45, 45, 45, 0.6);
            border-radius: 12px;
            border: 1px solid rgba(218, 165, 32, 0.2);
            transition: all 0.3s;
        }
        
        .order-item:hover {
            background: rgba(218, 165, 32, 0.15);
            border-color: var(--gold);
        }
        
        .item-qty {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: var(--black);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.4);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--gold-lighter);
            margin-bottom: 5px;
        }
        
        .item-notes {
            font-size: 14px;
            color: rgba(218, 165, 32, 0.7);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .badge-preparing {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .badge-ready {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .order-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-action {
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }
        
        .btn-preparing {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-ready {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-served {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--black);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(218, 165, 32, 0.6);
        }
        
        .empty-state i {
            font-size: 100px;
            margin-bottom: 25px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--gold-light);
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="kitchen-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-fire-burner me-3"></i>Kitchen Display</h1>
                <p>Monitor pesanan real-time</p>
            </div>
            <div class="text-end">
                <div class="clock" id="clock"></div>
                <a href="index.php" class="btn btn-light btn-sm mt-2">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <div class="status-tabs">
        <button class="status-tab active" data-status="all">
            <i class="fas fa-th me-2"></i>Semua
        </button>
        <button class="status-tab" data-status="pending">
            <i class="fas fa-clock me-2"></i>Pending
        </button>
        <button class="status-tab" data-status="preparing">
            <i class="fas fa-fire me-2"></i>Preparing
        </button>
        <button class="status-tab" data-status="ready">
            <i class="fas fa-check me-2"></i>Ready
        </button>
    </div>
    
    <div class="orders-grid" id="ordersGrid">
        <?php if ($result_orders->num_rows > 0): ?>
            <?php 
            $grouped = [];
            while ($order = $result_orders->fetch_assoc()) {
                $grouped[$order['transaction_id']]['code'] = $order['transaction_code'];
                $grouped[$order['transaction_id']]['date'] = $order['transaction_date'];
                $grouped[$order['transaction_id']]['items'][] = $order;
            }
            
            foreach ($grouped as $trx_id => $data): 
                $first_item = $data['items'][0];
                $status = $first_item['status'];
                $time_diff = time() - strtotime($first_item['created_at']);
                $minutes = floor($time_diff / 60);
            ?>
                <div class="order-card <?= $status ?>" data-status="<?= $status ?>">
                    <div class="order-header">
                        <div class="order-code"><?= $data['code'] ?></div>
                        <div class="order-time">
                            <i class="far fa-clock me-1"></i><?= $minutes ?> min
                        </div>
                    </div>
                    
                    <?php foreach ($data['items'] as $item): ?>
                        <div class="order-item">
                            <div class="item-qty"><?= $item['quantity'] ?>x</div>
                            <div class="item-details">
                                <div class="item-name"><?= $item['product_name'] ?></div>
                                <?php if ($item['notes']): ?>
                                    <div class="item-notes">
                                        <i class="fas fa-sticky-note me-1"></i><?= $item['notes'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge badge-<?= $item['status'] ?>">
                                <?= ucfirst($item['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="order-actions">
                        <?php if ($status === 'pending'): ?>
                            <button class="btn-action btn-preparing" onclick="updateStatus(<?= $first_item['item_id'] ?>, 'preparing')">
                                <i class="fas fa-fire me-1"></i>Mulai Masak
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'preparing'): ?>
                            <button class="btn-action btn-ready" onclick="updateStatus(<?= $first_item['item_id'] ?>, 'ready')">
                                <i class="fas fa-check me-1"></i>Siap Sajikan
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'ready'): ?>
                            <button class="btn-action btn-served" onclick="updateStatus(<?= $first_item['item_id'] ?>, 'served')">
                                <i class="fas fa-utensils me-1"></i>Selesai
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>Tidak Ada Pesanan</h3>
                <p>Semua pesanan sudah selesai diproses</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // Update clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            document.getElementById('clock').textContent = time;
        }
        
        updateClock();
        setInterval(updateClock, 1000);
        
        // Status filter
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const status = this.getAttribute('data-status');
                document.querySelectorAll('.order-card').forEach(card => {
                    if (status === 'all') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = card.getAttribute('data-status') === status ? 'block' : 'none';
                    }
                });
            });
        });
        
        // Update status
        async function updateStatus(itemId, newStatus) {
            try {
                const response = await fetch('api/update_kitchen_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, status: newStatus })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>