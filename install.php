<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stasiun Kerang - Database Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #333; margin-bottom: 10px; font-size: 32px; }
        h2 { color: #666; font-size: 18px; font-weight: normal; margin-bottom: 30px; }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .info-box code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .step {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #95a5a6;
        }
        .step.success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .step.error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .step.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .credentials {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid #ffc107;
        }
        .credentials h3 { color: #856404; margin-bottom: 15px; }
        .credentials p { margin: 8px 0; font-size: 16px; }
        .credentials code {
            background: white;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            margin-top: 20px;
        }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .summary {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦐 Stasiun Kerang POS</h1>
        <h2>Database Installation Wizard</h2>

        <div class="info-box">
            <strong>📊 Database Configuration:</strong><br>
            Host: <code><?php echo htmlspecialchars($host); ?></code><br>
            Port: <code><?php echo htmlspecialchars($port); ?></code><br>
            Database: <code><?php echo htmlspecialchars($dbname); ?></code><br>
            User: <code><?php echo htmlspecialchars($user); ?></code>
        </div>

<?php
$tables_created = 0;
$data_inserted = 0;
$errors = [];

try {
    echo "<div class='step info'>🔌 Connecting to database...</div>";
    
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    echo "<div class='step success'>✅ Connected to database successfully!</div>";
    
    // Table: users
    echo "<div class='step info'>📝 Creating users table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `phone` VARCHAR(20),
        `role` ENUM('admin','kasir') DEFAULT 'kasir',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Users table created</div>";
        $tables_created++;
    }
    
    // Table: categories
    echo "<div class='step info'>📝 Creating categories table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `categories` (
        `category_id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `icon` VARCHAR(50),
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Categories table created</div>";
        $tables_created++;
    }
    
    // Table: products
    echo "<div class='step info'>📝 Creating products table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `products` (
        `product_id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT NOT NULL,
        `product_name` VARCHAR(200) NOT NULL,
        `description` TEXT,
        `image` VARCHAR(255),
        `cost_price` DECIMAL(12,2) DEFAULT 0,
        `selling_price` DECIMAL(12,2) NOT NULL,
        `stock_quantity` INT DEFAULT 0,
        `min_stock` INT DEFAULT 5,
        `unit` VARCHAR(50) DEFAULT 'porsi',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Products table created</div>";
        $tables_created++;
    }
    
    // Table: members
    echo "<div class='step info'>📝 Creating members table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `members` (
        `member_id` INT AUTO_INCREMENT PRIMARY KEY,
        `member_code` VARCHAR(50) NOT NULL UNIQUE,
        `member_name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20),
        `email` VARCHAR(100),
        `address` TEXT,
        `points` INT DEFAULT 0,
        `total_spent` DECIMAL(15,2) DEFAULT 0,
        `join_date` DATE NOT NULL,
        `birth_date` DATE,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Members table created</div>";
        $tables_created++;
    }
    
    // Table: transactions
    echo "<div class='step info'>📝 Creating transactions table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `transactions` (
        `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `member_id` INT,
        `transaction_date` DATETIME NOT NULL,
        `subtotal` DECIMAL(15,2) NOT NULL,
        `discount` DECIMAL(15,2) DEFAULT 0,
        `tax` DECIMAL(15,2) DEFAULT 0,
        `total_amount` DECIMAL(15,2) NOT NULL,
        `payment_method` ENUM('cash','qris','transfer','debit','credit') DEFAULT 'cash',
        `cash_received` DECIMAL(15,2) DEFAULT 0,
        `change_amount` DECIMAL(15,2) DEFAULT 0,
        `points_earned` INT DEFAULT 0,
        `notes` TEXT,
        `status` ENUM('pending','completed','cancelled') DEFAULT 'completed',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
        FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Transactions table created</div>";
        $tables_created++;
    }
    
    // Table: transaction_items
    echo "<div class='step info'>📝 Creating transaction_items table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `transaction_items` (
        `item_id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(200) NOT NULL,
        `quantity` INT NOT NULL,
        `unit_price` DECIMAL(12,2) NOT NULL,
        `subtotal` DECIMAL(15,2) NOT NULL,
        `notes` TEXT,
        `status` ENUM('pending','preparing','ready','served') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Transaction items table created</div>";
        $tables_created++;
    }
    
    // Table: stock_history
    echo "<div class='step info'>📝 Creating stock_history table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `stock_history` (
        `history_id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `transaction_id` INT,
        `type` ENUM('in','out','adjustment') NOT NULL,
        `quantity` INT NOT NULL,
        `stock_before` INT NOT NULL,
        `stock_after` INT NOT NULL,
        `notes` TEXT,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
        FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) ON DELETE SET NULL,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Stock history table created</div>";
        $tables_created++;
    }
    
    // Table: expenses
    echo "<div class='step info'>📝 Creating expenses table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `expenses` (
        `expense_id` INT AUTO_INCREMENT PRIMARY KEY,
        `expense_category` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `description` TEXT,
        `expense_date` DATE NOT NULL,
        `payment_method` ENUM('cash','transfer','debit','credit') DEFAULT 'cash',
        `receipt_number` VARCHAR(100),
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Expenses table created</div>";
        $tables_created++;
    }
    
    // Table: settings
    echo "<div class='step info'>📝 Creating settings table...</div>";
    if ($conn->query("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NOT NULL,
        `description` TEXT,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        echo "<div class='step success'>✓ Settings table created</div>";
        $tables_created++;
    }
    
    // Insert default users
    echo "<div class='step info'>👤 Inserting default users...</div>";
    $conn->query("INSERT IGNORE INTO `users` (username, password, full_name, email, role) VALUES 
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@stasiunkerang.com', 'admin'),
        ('kasir1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir1@stasiunkerang.com', 'kasir')");
    echo "<div class='step success'>✓ Default users inserted</div>";
    $data_inserted++;
    
    // Insert categories
    echo "<div class='step info'>📂 Inserting menu categories...</div>";
    $conn->query("INSERT IGNORE INTO `categories` (category_name, description, icon) VALUES 
        ('Gerbong', 'Menu paket gerbong', 'fa-box'),
        ('Kepiting Mix', 'Menu kepiting mix', 'fa-utensils'),
        ('Lobster Mix', 'Menu lobster mix', 'fa-fish'),
        ('Cumi Udang', 'Menu cumi dan udang', 'fa-shrimp'),
        ('Ayam Ceker', 'Menu ayam dan ceker', 'fa-drumstick-bite'),
        ('Kerang Kiloan', 'Menu kerang kiloan', 'fa-clam'),
        ('Sayur', 'Menu sayuran', 'fa-leaf'),
        ('Minuman', 'Menu minuman', 'fa-glass-water'),
        ('Nasi', 'Nasi putih', 'fa-bowl-rice'),
        ('Paket Spesial', 'Paket menu spesial', 'fa-star')");
    echo "<div class='step success'>✓ Categories inserted (10 categories)</div>";
    $data_inserted++;
    
    // Insert sample products from Stasiun Kerang
    echo "<div class='step info'>🍽️ Inserting Stasiun Kerang menu items...</div>";
    $conn->query("INSERT IGNORE INTO `products` (category_id, product_name, description, cost_price, selling_price, stock_quantity, unit) VALUES
        (1, 'Gerbong 1 (3 Varian)', 'Paket 3 varian kerang', 60000, 130000, 20, 'paket'),
        (1, 'Gerbong 2 (4 Varian)', 'Paket 4 varian kerang', 80000, 160000, 20, 'paket'),
        (1, 'Gerbong 3 (5 Varian)', 'Paket 5 varian kerang', 100000, 180000, 15, 'paket'),
        (2, 'Kepiting Mix 1', 'Kepiting + 1 varian kerang', 50000, 85000, 15, 'porsi'),
        (2, 'Kepiting Mix 2', 'Kepiting + 2 varian kerang', 60000, 100000, 15, 'porsi'),
        (3, 'Lobster Mix 1', 'Lobster + 1 varian kerang', 60000, 100000, 10, 'porsi'),
        (3, 'Lobster Mix 2', 'Lobster + 2 varian kerang', 70000, 120000, 10, 'porsi'),
        (4, 'Cumi Goreng Mentega', 'Cumi saus mentega', 20000, 35000, 30, 'porsi'),
        (4, 'Udang Goreng Mentega', 'Udang saus mentega', 22000, 40000, 25, 'porsi'),
        (5, 'Ceker Balado', 'Ceker sambal balado', 8000, 15000, 50, 'porsi'),
        (5, 'Ayam Goreng Mentega', 'Ayam saus mentega', 10000, 18000, 40, 'porsi'),
        (6, 'Kerang Hijau', 'Per kilogram', 25000, 45000, 50, 'kg'),
        (6, 'Kerang Batik', 'Per kilogram', 35000, 65000, 30, 'kg'),
        (7, 'Kangkung Cah Polos', 'Tumis kangkung', 5000, 10000, 60, 'porsi'),
        (8, 'Teh Manis', 'Teh manis dingin', 2500, 5000, 100, 'gelas'),
        (8, 'Mineral', 'Air mineral', 2500, 5000, 100, 'botol'),
        (9, 'Nasi Putih', 'Nasi putih hangat', 2500, 5000, 200, 'porsi'),
        (10, 'Kepiting Porsi', 'Kepiting 1 porsi', 70000, 120000, 10, 'porsi'),
        (10, 'Lobster Porsi', 'Lobster 1 porsi', 90000, 130000, 8, 'porsi')");
    echo "<div class='step success'>✓ Menu items inserted (19 products)</div>";
    $data_inserted++;
    
    // Insert settings
    echo "<div class='step info'>⚙️ Inserting system settings...</div>";
    $conn->query("INSERT IGNORE INTO `settings` (setting_key, setting_value, description) VALUES 
        ('restaurant_name', 'Stasiun Kerang', 'Nama restoran'),
        ('restaurant_address', 'Sindangsari no 10 E, RT.01/RW.04, Kota Bogor, Jawa Barat', 'Alamat restoran'),
        ('restaurant_phone', '0813-8474-4206', 'Telepon restoran'),
        ('tax_rate', '10', 'Persentase pajak'),
        ('points_per_1000', '1', 'Poin per Rp 1.000'),
        ('currency', 'Rp', 'Simbol mata uang'),
        ('receipt_footer', 'Terima kasih atas kunjungan Anda!', 'Footer struk')");
    echo "<div class='step success'>✓ System settings inserted</div>";
    $data_inserted++;
    
    $conn->close();
    
    echo "<div class='summary'>";
    echo "<h2 style='color: #28a745;'>🎉 Installation Completed Successfully!</h2>";
    echo "<div class='info-box'>";
    echo "<strong>📊 Installation Summary:</strong><br>";
    echo "✓ Tables created: <code>$tables_created</code><br>";
    echo "✓ Data sets inserted: <code>$data_inserted</code><br>";
    echo "✓ Products added: <code>19</code><br>";
    echo "✓ Categories created: <code>10</code>";
    echo "</div>";
    
    echo "<div class='credentials'>";
    echo "<h3>🔑 Default Login Credentials</h3>";
    echo "<p><strong>Username:</strong> <code>admin</code></p>";
    echo "<p><strong>Password:</strong> <code>admin123</code></p>";
    echo "<p style='margin-top: 15px; font-size: 14px; color: #856404;'><em>⚠️ Please change your password after first login!</em></p>";
    echo "</div>";
    
    echo "<a href='index.php' class='btn'>🚀 Launch Application</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<strong>❌ Installation Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<div class='info-box' style='border-left-color: #dc3545; background: #f8d7da;'>";
    echo "<strong style='color: #721c24;'>Troubleshooting Tips:</strong><br>";
    echo "1. Check if database credentials are correct<br>";
    echo "2. Ensure database service is running<br>";
    echo "3. Check Railway logs for more details<br>";
    echo "4. Verify network connectivity to database";
    echo "</div>";
}
?>

    </div>
</body>
</html>
