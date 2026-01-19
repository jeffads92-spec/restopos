<?php
/**
 * Stasiun Kerang POS - Database Installer
 */

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
        </style>
    </head>
    <body>
        <div class='message'>
            <h1>✅ Already Installed</h1>
            <p>Database has been installed successfully.</p>
            <a href='index.php' class='btn'>Go to Application</a>
        </div>
    </body>
    </html>
    ");
}

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';

if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    
    $logs = [];
    $tables = 0;
    
    try {
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        $logs[] = "Connected to database";
        
        // Create tables one by one with simpler syntax
        
        // Users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role ENUM('admin','kasir') DEFAULT 'kasir',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: users";
        }
        
        // Categories table
        $sql = "CREATE TABLE IF NOT EXISTS categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: categories";
        }
        
        // Products table
        $sql = "CREATE TABLE IF NOT EXISTS products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            cost_price DECIMAL(12,2) DEFAULT 0.00,
            selling_price DECIMAL(12,2) NOT NULL,
            stock_quantity INT DEFAULT 0,
            min_stock INT DEFAULT 5,
            unit VARCHAR(50) DEFAULT 'porsi',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: products";
        }
        
        // Members table
        $sql = "CREATE TABLE IF NOT EXISTS members (
            member_id INT AUTO_INCREMENT PRIMARY KEY,
            member_code VARCHAR(50) NOT NULL UNIQUE,
            member_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            address TEXT,
            points INT DEFAULT 0,
            total_spent DECIMAL(15,2) DEFAULT 0.00,
            join_date DATE NOT NULL,
            birth_date DATE,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: members";
        }
        
        // Transactions table
        $sql = "CREATE TABLE IF NOT EXISTS transactions (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_code VARCHAR(50) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            member_id INT,
            transaction_date DATETIME NOT NULL,
            subtotal DECIMAL(15,2) NOT NULL,
            discount DECIMAL(15,2) DEFAULT 0.00,
            tax DECIMAL(15,2) DEFAULT 0.00,
            total_amount DECIMAL(15,2) NOT NULL,
            payment_method ENUM('cash','qris','transfer','debit','credit') DEFAULT 'cash',
            cash_received DECIMAL(15,2) DEFAULT 0.00,
            change_amount DECIMAL(15,2) DEFAULT 0.00,
            points_earned INT DEFAULT 0,
            notes TEXT,
            status ENUM('pending','completed','cancelled') DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: transactions";
        }
        
        // Transaction items table
        $sql = "CREATE TABLE IF NOT EXISTS transaction_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(12,2) NOT NULL,
            subtotal DECIMAL(15,2) NOT NULL,
            notes TEXT,
            status ENUM('pending','preparing','ready','served') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: transaction_items";
        }
        
        // Stock history table
        $sql = "CREATE TABLE IF NOT EXISTS stock_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            transaction_id INT,
            type ENUM('in','out','adjustment') NOT NULL,
            quantity INT NOT NULL,
            stock_before INT NOT NULL,
            stock_after INT NOT NULL,
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
            FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: stock_history";
        }
        
        // Expenses table
        $sql = "CREATE TABLE IF NOT EXISTS expenses (
            expense_id INT AUTO_INCREMENT PRIMARY KEY,
            expense_category VARCHAR(100) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            description TEXT,
            expense_date DATE NOT NULL,
            payment_method ENUM('cash','transfer','debit','credit') DEFAULT 'cash',
            receipt_number VARCHAR(100),
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: expenses";
        }
        
        // Settings table
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            setting_id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $tables++;
            $logs[] = "Created table: settings";
        }
        
        // Insert default users
        $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $sql = "INSERT IGNORE INTO users (username, password, full_name, email, role) VALUES 
            ('admin', '$password_hash', 'Administrator', 'admin@stasiunkerang.com', 'admin'),
            ('kasir1', '$password_hash', 'Kasir 1', 'kasir1@stasiunkerang.com', 'kasir')";
        $conn->query($sql);
        $logs[] = "Inserted default users";
        
        // Insert categories
        $sql = "INSERT IGNORE INTO categories (category_name, description, icon) VALUES 
            ('Gerbong', 'Menu paket gerbong', 'fa-box'),
            ('Kepiting Mix', 'Menu kepiting mix', 'fa-utensils'),
            ('Lobster Mix', 'Menu lobster mix', 'fa-fish'),
            ('Cumi Udang', 'Menu cumi dan udang', 'fa-shrimp'),
            ('Ayam Ceker', 'Menu ayam dan ceker', 'fa-drumstick-bite'),
            ('Kerang Kiloan', 'Menu kerang kiloan', 'fa-clam'),
            ('Sayur', 'Menu sayuran', 'fa-leaf'),
            ('Minuman', 'Menu minuman', 'fa-glass-water'),
            ('Nasi', 'Nasi putih', 'fa-bowl-rice'),
            ('Paket Spesial', 'Paket spesial', 'fa-star')";
        $conn->query($sql);
        $logs[] = "Inserted categories";
        
        // Insert settings
        $sql = "INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES 
            ('restaurant_name', 'Stasiun Kerang', 'Nama restoran'),
            ('restaurant_address', 'Depok, Jawa Barat', 'Alamat'),
            ('tax_rate', '10', 'Pajak persen'),
            ('currency', 'Rp', 'Mata uang')";
        $conn->query($sql);
        $logs[] = "Inserted settings";
        
        $conn->close();
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Database installed successfully!',
            'tables_created' => $tables,
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
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stasiun Kerang - Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
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
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 30px; }
        .info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info code { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 13px; }
        .btn {
            width: 100%;
            padding: 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #2980b9; }
        .btn:disabled { background: #95a5a6; cursor: not-allowed; }
        #result { margin-top: 20px; padding: 20px; border-radius: 8px; display: none; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .loading { display: none; text-align: center; margin-top: 20px; }
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
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .log div { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦐 Stasiun Kerang POS</h1>
        
        <div class="info">
            <strong>Database Configuration:</strong><br>
            Host: <code><?= htmlspecialchars($host) ?></code><br>
            Port: <code><?= htmlspecialchars($port) ?></code><br>
            Database: <code><?= htmlspecialchars($dbname) ?></code>
        </div>
        
        <button class="btn" id="installBtn" onclick="install()">🚀 Install Database</button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Installing...</p>
        </div>
        
        <div id="result"></div>
        <div class="log" id="log"></div>
    </div>
    
    <script>
        async function install() {
            const btn = document.getElementById('installBtn');
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const log = document.getElementById('log');
            
            btn.disabled = true;
            loading.style.display = 'block';
            result.style.display = 'none';
            log.style.display = 'none';
            log.innerHTML = '';
            
            try {
                const response = await fetch('?action=install', { method: 'POST' });
                const data = await response.json();
                
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `
                        <h3>✅ Success!</h3>
                        <p>Tables created: ${data.tables_created}</p>
                        <hr style="margin: 15px 0">
                        <p><strong>Login:</strong></p>
                        <p>Username: <code>admin</code></p>
                        <p>Password: <code>admin123</code></p>
                        <p style="margin-top: 15px">
                            <a href="index.php" style="color: #3498db; font-weight: 600">Go to App →</a>
                        </p>
                    `;
                    
                    log.style.display = 'block';
                    data.logs.forEach(l => {
                        const div = document.createElement('div');
                        div.textContent = '> ' + l;
                        log.appendChild(div);
                    });
                } else {
                    result.className = 'error';
                    result.innerHTML = `
                        <h3>❌ Failed</h3>
                        <p>${data.message}</p>
                        <pre style="background: #fff; padding: 10px; border-radius: 5px; overflow-x: auto">${data.error}</pre>
                    `;
                    btn.disabled = false;
                }
            } catch (error) {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `<h3>❌ Error</h3><p>${error.message}</p>`;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
