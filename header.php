<?php
require_once 'config.php';
checkLogin();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#DAA520">
    <title><?= ucfirst($current_page) ?> - <?= APP_NAME ?></title>
    
    <!-- Preconnect for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    
    <!-- Gold Premium Theme CSS -->
    <style>
        /**
         * 🏆 SMART RESTO POS - INLINE GOLD PREMIUM THEME
         * Tema konsisten untuk semua halaman dengan animasi mewah
         * Optimized untuk Mobile & Desktop
         */

        :root {
            /* Gold Color Palette */
            --gold-darkest: #6B5000;
            --gold-dark: #B8860B;
            --gold: #DAA520;
            --gold-light: #FFD700;
            --gold-lighter: #FFF8DC;
            --gold-lightest: #FFFEF7;
            
            /* Black & Gray Palette */
            --black: #0a0a0a;
            --black-light: #1a1a1a;
            --gray-darkest: #2d2d2d;
            --gray-dark: #3a3a3a;
            --gray: #4a4a4a;
            --gray-light: #6b7280;
            --gray-lighter: #9ca3af;
            
            /* Status Colors */
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --info: #3b82f6;
            --info-light: #dbeafe;
            
            /* Shadows */
            --shadow-gold-sm: 0 2px 8px rgba(218, 165, 32, 0.15);
            --shadow-gold-md: 0 4px 16px rgba(218, 165, 32, 0.25);
            --shadow-gold-lg: 0 8px 30px rgba(218, 165, 32, 0.35);
            --shadow-gold-xl: 0 20px 50px rgba(218, 165, 32, 0.45);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            
            /* Transitions */
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Sidebar */
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

<script src="assets/js/gold-animations.js"></script>
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
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
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-darkest);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--gold-dark), var(--gold));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--gold), var(--gold-light));
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            bottom: 0;
            width: 280px;
            max-width: 85vw;
            background: linear-gradient(180deg, var(--black) 0%, var(--gray-darkest) 100%);
            padding: 0;
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(218, 165, 32, 0.3);
            overflow-y: auto;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: 2px solid var(--gold-dark);
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 100%);
            border-bottom: 3px solid var(--gold-light);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-header::before {
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
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-10px, -10px) rotate(180deg); }
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--black);
            text-decoration: none;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-brand-icon {
            width: 48px;
            height: 48px;
            background: var(--black);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--gold-light);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: 2px solid var(--gold-light);
            flex-shrink: 0;
        }
        
        .sidebar-brand-text h3 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
        }
        
        .sidebar-brand-text p {
            font-size: 10px;
            margin: 0;
            opacity: 0.9;
        }
        
        .sidebar-nav {
            padding: 15px 10px;
        }
        
        .nav-section-title {
            color: var(--gold);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 12px 12px 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
            font-size: 14px;
            border: 1px solid transparent;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, rgba(218,165,32,0.15), rgba(255,215,0,0.15));
            color: var(--gold-light);
            transform: translateX(5px);
            border: 1px solid var(--gold-dark);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--black);
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(218, 165, 32, 0.4);
            border: 1px solid var(--gold-light);
        }
        
        .nav-link i {
            width: 20px;
            font-size: 16px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
            padding-top: 65px;
        }
        
        /* Topbar */
        .topbar {
            background: linear-gradient(135deg, var(--black) 0%, var(--gray-darkest) 100%);
            padding: 12px 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 2px solid var(--gold-dark);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        
        .menu-toggle {
            background: var(--gold);
            border: none;
            font-size: 20px;
            color: var(--black);
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(218, 165, 32, 0.4);
        }
        
        .menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.6);
        }
        
        .menu-toggle:active {
            transform: scale(0.95);
        }
        
        .page-title {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .topbar-item {
            display: none;
            align-items: center;
            gap: 6px;
            color: var(--gold-light);
            font-size: 12px;
        }
        
        .topbar-item i {
            font-size: 14px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--gold-light);
            box-shadow: 0 2px 8px rgba(218, 165, 32, 0.4);
        }
        
        .user-menu:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.6);
        }
        
        .user-menu:active {
            transform: scale(0.95);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--black);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-light);
            font-weight: 700;
            font-size: 14px;
            border: 2px solid var(--gold-light);
            flex-shrink: 0;
        }
        
        .user-info {
            display: none;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--black);
            line-height: 1.2;
        }
        
        .user-role {
            font-size: 10px;
            color: var(--gray-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Container */
        .container-fluid {
            padding: 15px;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            background: white;
            overflow: hidden;
            border: 2px solid var(--gold-lighter);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(218, 165, 32, 0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 100%);
            color: var(--black);
            padding: 15px 20px;
            border-radius: 14px 14px 0 0 !important;
            border: none;
            border-bottom: 3px solid var(--gold-light);
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .card-body {
            padding: 20px;
            color: var(--black);
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:active {
            transform: scale(0.97);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--black);
            border: 2px solid var(--gold-light);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.4);
        }
        
        .btn-primary:hover, .btn-primary:active, .btn-primary:focus {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--black);
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Table */
        .table {
            margin: 0;
            color: var(--black);
            font-size: 13px;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--gold-lighter), #fff8dc);
            border-bottom: 2px solid var(--gold-dark);
            color: var(--black);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gold-lighter);
        }
        
        .table tbody tr:hover {
            background: var(--gold-lighter);
        }
        
        /* Forms */
        .form-label {
            font-weight: 600;
            color: var(--black);
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .form-control, .form-select {
            padding: 10px 14px;
            border: 2px solid var(--gold-lighter);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: var(--black);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
            outline: none;
        }
        
        /* Tablet & Desktop */
        @media (min-width: 768px) {
            .sidebar {
                left: 0;
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .menu-toggle {
                display: none;
            }
            
            .sidebar-overlay {
                display: none;
            }
            
            .page-title {
                font-size: 22px;
            }
            
            .topbar {
                padding: 15px 25px;
            }
            
            .topbar-item {
                display: flex;
                font-size: 13px;
            }
            
            .user-info {
                display: flex;
                flex-direction: column;
            }
            
            .user-menu {
                padding: 8px 15px;
            }
            
            .user-avatar {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }
            
            .container-fluid {
                padding: 25px;
            }
            
            .card-header h5 {
                font-size: 18px;
            }
            
            .btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
        
        /* Loading Animation */
        .spinner-gold {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(218, 165, 32, 0.2);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="sidebar-brand-text">
                    <h3><?= APP_NAME ?></h3>
                    <p>Premium POS</p>
                </div>
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section-title">MAIN MENU</div>
            
            <a href="index.php" class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="pos.php" class="nav-link <?= $current_page === 'pos' ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i>
                <span>Point of Sale</span>
            </a>
            
            <a href="kitchen.php" class="nav-link <?= $current_page === 'kitchen' ? 'active' : '' ?>">
                <i class="fas fa-fire-burner"></i>
                <span>Kitchen Display</span>
            </a>
            
            <div class="nav-section-title">MANAGEMENT</div>
            
            <a href="products.php" class="nav-link <?= $current_page === 'products' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i>
                <span>Products</span>
            </a>
            
            <a href="inventory.php" class="nav-link <?= $current_page === 'inventory' ? 'active' : '' ?>">
                <i class="fas fa-warehouse"></i>
                <span>Inventory</span>
            </a>
            
            <a href="members.php" class="nav-link <?= $current_page === 'members' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>
                <span>VIP Members</span>
            </a>
            
            <?php if (isAdmin()): ?>
            <a href="expenses.php" class="nav-link <?= $current_page === 'expenses' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-trend-up"></i>
                <span>Expenses</span>
            </a>
            <?php endif; ?>
            
            <div class="nav-section-title">REPORTS</div>
            
            <a href="transactions.php" class="nav-link <?= $current_page === 'transactions' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Transactions</span>
            </a>
            
            <?php if (isAdmin()): ?>
            <a href="reports.php" class="nav-link <?= $current_page === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Reports</span>
            </a>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <div class="nav-section-title">SETTINGS</div>
            
            <a href="users.php" class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users-gear"></i>
                <span>Users</span>
            </a>
            
            <a href="settings.php" class="nav-link <?= $current_page === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-sliders"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
            
            <div class="nav-section-title">ACCOUNT</div>
            
            <a href="logout.php" class="nav-link" onclick="return confirm('Logout from system?')">
                <i class="fas fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?= ucwords(str_replace('_', ' ', $current_page)) ?></h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-item">
                    <i class="far fa-clock"></i>
                    <span id="currentTime"></span>
                </div>
                
                <div class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr(getUserName(), 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= getUserName() ?></div>
                        <div class="user-role"><?= getUserRole() ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="container-fluid">
<?php // Content will be loaded here ?>