<?php
/**
 * Smart Resto POS - Database Installer for Railway
 * Creates database directly without SQL file
 */

// Prevent running if already installed
$lock_file = __DIR__ . '/.installed.lock';
if (file_exists($lock_file)) {
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Already Installed</title>
        <style>
            body { font-family: sans-serif; padding: 50px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .message { background: white; padding: 40px; border-radius: 20px; max-width: 500px; margin: 0 auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
            h1 { color: #27ae60; }
            .btn { display: inline-block; margin: 10px; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
            .btn-danger { background: #e74c3c; }
        </style>
    </head>
    <body>
        <div class='message'>
            <h1>✅ Already Installed</h1>
            <p>Database has been installed successfully.</p>
            <a href='index.php' class='btn'>Go to Application</a>
            <form method='post' action='?action=reset' style='margin-top: 20px;'>
                <button type='submit' class='btn btn-danger' onclick='return confirm(\"Reset installation?\")'>Reset & Reinstall</button>
            </form>
        </div>
    </body>
    </html>
    ");
}

// Handle reset
if (isset($_GET['action']) && $_GET['action'] === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
    header('Location: install.php');
    exit;
}

// Get Railway MySQL credentials
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stasiun Kerang - Database Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
        h2 { color: #666; font-size: 16px; font-weight: normal; margin-bottom: 30px; }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .info-box strong { color: #2c3e50; display: block; margin-bottom: 10px; }
        .info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: #495057;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
        }
        .btn:hover { background: #2980b9; }
        .btn:disabled { background: #95a5a6; cursor: not-allowed; }
        #result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .log { 
            background: #2c3e50; 
            color: #ecf0f1; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 15px; 
            font-family: 'Courier New', monospace; 
            font-size: 11px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .log-line { margin: 3px 0; padding: 2px 0; }
        .log-success { color: #2ecc71; }
        .log-error { color: #e74c3c; }
        .log-info { color: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦐 Stasiun Kerang POS</h1>
        <h2>Database Installation Wizard</h2>

        <div class="info-box">
            <strong>📊 Database Configuration:</strong>
            Host: <code><?php echo htmlspecialchars($host); ?></code><br>
            Port: <code><?php echo htmlspecialchars($port); ?></code><br>
            Database: <code><?php echo htmlspecialchars($dbname); ?></code><br>
            User: <code><?php echo htmlspecialchars($user); ?></code>
        </div>

        <div class="info-box" style="border-left-color: #e74c3c;">
            <strong>⚠️ Important:</strong>
            This will create all database tables for Stasiun Kerang POS system.
        </div>

        <button class="btn" id="installBtn" onclick="installDatabase()">
            🚀 Install Database
        </button>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Installing database, please wait...</p>
        </div>

        <div id="result"></div>
        <div class="log" id="log"></div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            log.style.display = 'block';
            const line = document.createElement('div');
            line.className = 'log-line log-' + type;
            line.textContent = '> ' + message;
            log.appendChild(line);
            log.scrollTop = log.scrollHeight;
        }

        async function installDatabase() {
            const btn = document.getElementById('installBtn');
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            btn.disabled = true;
            loading.style.display = 'block';
            result.style.display = 'none';
            document.getElementById('log').innerHTML = '';
            
            addLog('Starting database installation...', 'info');

            try {
                const response = await fetch('?action=install', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `
                        <h3>✅ Installation Successful!</h3>
                        <p>${data.message}</p>
                        <p><strong>Tables created:</strong> ${data.tables_created}</p>
                        <hr style="margin: 15px 0;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <p><strong>📝 Default Login:</strong></p>
                            <p>Username: <code style="background: #fff; padding: 5px 10px;">admin</code></p>
                            <p>Password: <code style="background: #fff; padding: 5px 10px;">admin123</code></p>
                        </div>
                        <p style="margin-top: 15px;">
                            <a href="index.php" class="btn">Go to Application →</a>
                        </p>
                    `;
                    data.logs.forEach(log => {
                        addLog(log, log.includes('✓') ? 'success' : 'info');
                    });
                } else {
                    result.className = 'error';
                    result.innerHTML = `
                        <h3>❌ Installation Failed</h3>
                        <p>${data.message}</p>
                        <pre style="background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 11px;">${data.error}</pre>
                    `;
                    addLog('Installation failed: ' + data.error, 'error');
                    btn.disabled = false;
                }
            } catch (error) {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `
                    <h3>❌ Installation Error</h3>
                    <p>An unexpected error occurred: ${error.message}</p>
                `;
                addLog('Error: ' + error.message, 'error');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>

<?php
// Handle installation request
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    
    $logs = [];
    $tables_created = 0;
    
    try {
        // Connect to database
        $logs[] = "Connecting to MySQL server...";
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        $logs[] = "✓ Connected to database";
        
        // Define all SQL statements as PHP array (no external file needed!)
        $sqls = [
            // Table: users
            "CREATE TABLE IF NOT EXISTS `users` (
              `user_id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `password` varchar(255) NOT NULL,
              `full_name` varchar(100) NOT NULL,
              `email` varchar(100) DEFAULT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
              `is_active` tinyint(1) DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`user_id`),
              UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: categories
            "CREATE TABLE IF NOT EXISTS `categories` (
              `category_id` int(11) NOT NULL AUTO_INCREMENT,
              `category_name` varchar(100) NOT NULL,
              `description` text DEFAULT NULL,
              `icon` varchar(50) DEFAULT NULL,
              `is_active` tinyint(1) DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: products
            "CREATE TABLE IF NOT EXISTS `products` (
              `product_id` int(11) NOT NULL AUTO_INCREMENT,
              `category_id` int(11) NOT NULL,
              `product_name` varchar(200) NOT NULL,
              `description` text DEFAULT NULL,
              `image` varchar(255) DEFAULT NULL,
              `cost_price` decimal(12,2) DEFAULT 0.00,
              `selling_price` decimal(12,2) NOT NULL,
              `stock_quantity` int(11) DEFAULT 0,
              `min_stock` int(11) DEFAULT 5,
              `unit` varchar(50) DEFAULT 'porsi',
              `is_active` tinyint(1) DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`product_id`),
              KEY `category_id` (`category_id`),
              CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: members
            "CREATE TABLE IF NOT EXISTS `members` (
              `member_id` int(11) NOT NULL AUTO_INCREMENT,
              `member_code` varchar(50) NOT NULL,
              `member_name` varchar(100) NOT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `email` varchar(100) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `points` int(11) DEFAULT 0,
              `total_spent` decimal(15,2) DEFAULT 0.00,
              `join_date` date NOT NULL,
              `birth_date` date DEFAULT NULL,
              `is_active` tinyint(1) DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`member_id`),
              UNIQUE KEY `member_code` (`member_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: transactions
            "CREATE TABLE IF NOT EXISTS `transactions` (
              `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
              `transaction_code` varchar(50) NOT NULL,
              `user_id` int(11) NOT NULL,
              `member_id` int(11) DEFAULT NULL,
              `transaction_date` datetime NOT NULL,
              `subtotal` decimal(15,2) NOT NULL,
              `discount` decimal(15,2) DEFAULT 0.00,
              `tax` decimal(15,2) DEFAULT 0.00,
              `total_amount` decimal(15,2) NOT NULL,
              `payment_method` enum('cash','qris','transfer','debit','credit') NOT NULL DEFAULT 'cash',
              `cash_received` decimal(15,2) DEFAULT 0.00,
              `change_amount` decimal(15,2) DEFAULT 0.00,
              `points_earned` int(11) DEFAULT 0,
              `notes` text DEFAULT NULL,
              `status` enum('pending','completed','cancelled') DEFAULT 'completed',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`transaction_id`),
              UNIQUE KEY `transaction_code` (`transaction_code`),
              KEY `user_id` (`user_id`),
              KEY `member_id` (`member_id`),
              CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
              CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: transaction_items
            "CREATE TABLE IF NOT EXISTS `transaction_items` (
              `item_id` int(11) NOT NULL AUTO_INCREMENT,
              `transaction_id` int(11) NOT NULL,
              `product_id` int(11) NOT NULL,
              `product_name` varchar(200) NOT NULL,
              `quantity` int(11) NOT NULL,
              `unit_price` decimal(12,2) NOT NULL,
              `subtotal` decimal(15,2) NOT NULL,
              `notes` text DEFAULT NULL,
              `status` enum('pending','preparing','ready','served') DEFAULT 'pending',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`item_id`),
              KEY `transaction_id` (`transaction_id`),
              KEY `product_id` (`product_id`),
              CONSTRAINT `fk_transaction_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_transaction_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: stock_history
            "CREATE TABLE IF NOT EXISTS `stock_history` (
              `history_id` int(11) NOT NULL AUTO_INCREMENT,
              `product_id` int(11) NOT NULL,
              `transaction_id` int(11) DEFAULT NULL,
              `type` enum('in','out','adjustment') NOT NULL,
              `quantity` int(11) NOT NULL,
              `stock_before` int(11) NOT NULL,
              `stock_after` int(11) NOT NULL,
              `notes` text DEFAULT NULL,
              `created_by` int(11) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`history_id`),
              KEY `product_id` (`product_id`),
              KEY `transaction_id` (`transaction_id`),
              KEY `created_by` (`created_by`),
              CONSTRAINT `fk_stock_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_stock_history_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE SET NULL,
              CONSTRAINT `fk_stock_history_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: expenses
            "CREATE TABLE IF NOT EXISTS `expenses` (
              `expense_id` int(11) NOT NULL AUTO_INCREMENT,
              `expense_category` varchar(100) NOT NULL,
              `amount` decimal(15,2) NOT NULL,
              `description` text DEFAULT NULL,
              `expense_date` date NOT NULL,
              `payment_method` enum('cash','transfer','debit','credit') DEFAULT 'cash',
              `receipt_number` varchar(100) DEFAULT NULL,
              `created_by` int(11) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`expense_id`),
              KEY `created_by` (`created_by`),
              CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Table: settings
            "CREATE TABLE IF NOT EXISTS `settings` (
              `setting_id` int(11) NOT NULL AUTO_INCREMENT,
              `setting_key` varchar(100) NOT NULL,
              `setting_value` text NOT NULL,
              `description` text DEFAULT NULL,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`setting_id`),
              UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        // Execute table creation
        foreach ($sqls as $sql) {
            if ($conn->query($sql)) {
                if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $sql, $matches)) {
                    $tables_created++;
                    $logs[] = "✓ Created table: " . $matches[1];
                }
            } else {
                if ($conn->errno != 1050) { // Ignore "table exists" error
                    $logs[] = "⚠ Warning: " . $conn->error;
                }
            }
        }
        
        // Insert default data
        $logs[] = "Inserting default data...";
        
        // Insert users
        $conn->query("INSERT IGNORE INTO users (username, password, full_name, email, role) VALUES 
            ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@stasiunkerang.com', 'admin'),
            ('kasir1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir1@stasiunkerang.com', 'kasir')");
        $logs[] = "✓ Inserted default users";
        
        // Insert categories
        $conn->query("INSERT IGNORE INTO categories (category_name, description, icon) VALUES 
            ('Gerbong', 'Menu paket gerbong', 'fa-box'),
            ('Kepiting Mix', 'Menu kepiting mix', 'fa-utensils'),
            ('Lobster Mix', 'Menu lobster mix', 'fa-fish'),
            ('Cumi Udang', 'Menu cumi dan udang', 'fa-shrimp'),
            ('Ayam Ceker', 'Menu ayam dan ceker', 'fa-drumstick-bite'),
            ('Kerang Kiloan', 'Menu kerang kiloan', 'fa-clam'),
            ('Sayur', 'Menu sayuran', 'fa-leaf'),
            ('Minuman', 'Menu minuman', 'fa-glass-water'),
            ('Nasi', 'Nasi putih', 'fa-bowl-rice'),
            ('Paket Spesial', 'Paket spesial', 'fa-star')");
        $logs[] = "✓ Inserted categories";
        
        // Insert settings
        $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES 
            ('restaurant_name', 'Stasiun Kerang', 'Nama restoran'),
            ('restaurant_address', 'Depok, Jawa Barat', 'Alamat restoran'),
            ('tax_rate', '10', 'Persentase pajak'),
            ('currency', 'Rp', 'Simbol mata uang')");
        $logs[] = "✓ Inserted settings";
        
        $conn->close();
        
        // Create lock file
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
        $logs[] = "✓ Installation completed";
        
        echo json_encode([
            'success' => true,
            'message' => 'Database installed successfully!',
            'tables_created' => $tables_created,
            'logs' => $logs
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Installation failed',
            'error' => $e->getMessage(),
            'logs' => $logs
        ]);
    }
    
    exit;
}
?>
