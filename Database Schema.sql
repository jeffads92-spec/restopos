-- Smart Resto POS - Database Schema
-- Created: 2025
-- Database: smart_resto_pos
-- Compatible with Railway MySQL

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    cost_price DECIMAL(12,2) DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    min_stock INT DEFAULT 5,
    unit VARCHAR(50) DEFAULT 'pcs',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_is_active (is_active),
    INDEX idx_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: members
CREATE TABLE IF NOT EXISTS members (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_code (member_code),
    INDEX idx_phone (phone),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transactions
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    member_id INT NULL,
    transaction_date DATETIME NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0.00,
    tax DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'qris', 'transfer', 'debit', 'credit') NOT NULL DEFAULT 'cash',
    cash_received DECIMAL(15,2) DEFAULT 0.00,
    change_amount DECIMAL(15,2) DEFAULT 0.00,
    points_earned INT DEFAULT 0,
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaction_items
CREATE TABLE IF NOT EXISTS transaction_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'preparing', 'ready', 'served') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: stock_history
CREATE TABLE IF NOT EXISTS stock_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    transaction_id INT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    stock_before INT NOT NULL,
    stock_after INT NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expenses
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    expense_category VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'transfer', 'debit', 'credit') DEFAULT 'cash',
    receipt_number VARCHAR(100),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_expense_date (expense_date),
    INDEX idx_category (expense_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: settings
CREATE TABLE IF NOT EXISTS settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- INSERT DEFAULT DATA
-- --------------------------------------------------------

-- Insert default users
-- Password for both: "password" (hashed with PHP password_hash)
INSERT INTO users (username, password, full_name, email, role, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@smartrestopos.com', 'admin', 1),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Satu', 'kasir1@smartrestopos.com', 'kasir', 1),
('kasir2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Dua', 'kasir2@smartrestopos.com', 'kasir', 1);

-- Insert default categories
INSERT INTO categories (category_name, description, icon, is_active) VALUES
('Makanan Utama', 'Menu makanan utama restoran', 'fa-utensils', 1),
('Minuman', 'Menu minuman panas dan dingin', 'fa-glass-water', 1),
('Snack', 'Menu cemilan dan appetizer', 'fa-cookie-bite', 1),
('Dessert', 'Menu pencuci mulut', 'fa-ice-cream', 1),
('Paket', 'Menu paket hemat', 'fa-box', 1);

-- Insert sample products
INSERT INTO products (category_id, product_name, description, cost_price, selling_price, stock_quantity, min_stock, unit, is_active) VALUES
-- Makanan Utama
(1, 'Nasi Goreng Spesial', 'Nasi goreng dengan telur, ayam, dan sayuran pilihan', 12000, 25000, 50, 10, 'porsi', 1),
(1, 'Mie Goreng', 'Mie goreng dengan sayuran segar', 8000, 18000, 50, 10, 'porsi', 1),
(1, 'Ayam Bakar', 'Ayam bakar dengan sambal kecap pedas', 15000, 30000, 30, 5, 'porsi', 1),
(1, 'Soto Ayam', 'Soto ayam kuning dengan nasi putih', 10000, 20000, 40, 10, 'porsi', 1),
(1, 'Nasi Uduk Komplit', 'Nasi uduk dengan lauk lengkap', 13000, 28000, 35, 8, 'porsi', 1),
(1, 'Nasi Rawon', 'Rawon daging sapi dengan nasi putih', 18000, 35000, 25, 5, 'porsi', 1),

-- Minuman
(2, 'Es Teh Manis', 'Es teh manis segar', 2000, 5000, 100, 20, 'gelas', 1),
(2, 'Jus Jeruk', 'Jus jeruk segar tanpa gula', 5000, 12000, 50, 10, 'gelas', 1),
(2, 'Kopi Hitam', 'Kopi hitam panas original', 3000, 8000, 80, 15, 'gelas', 1),
(2, 'Cappuccino', 'Cappuccino dengan foam susu', 6000, 15000, 60, 10, 'gelas', 1),
(2, 'Es Jeruk', 'Es jeruk segar dengan potongan buah', 3000, 8000, 75, 15, 'gelas', 1),
(2, 'Thai Tea', 'Thai tea original dengan susu', 5000, 12000, 55, 10, 'gelas', 1),

-- Snack
(3, 'Pisang Goreng', 'Pisang goreng crispy dengan keju', 3000, 8000, 40, 10, 'porsi', 1),
(3, 'Kentang Goreng', 'Kentang goreng dengan saus sambal', 5000, 12000, 35, 10, 'porsi', 1),
(3, 'Tahu Isi', 'Tahu isi sayuran dan daging', 4000, 10000, 45, 10, 'porsi', 1),
(3, 'Risoles Mayo', 'Risoles isi mayo dan sayuran', 4500, 11000, 38, 8, 'porsi', 1),

-- Dessert
(4, 'Es Krim Vanilla', 'Es krim vanilla premium', 7000, 15000, 25, 5, 'cup', 1),
(4, 'Pudding Coklat', 'Pudding coklat dengan vla', 4000, 10000, 30, 5, 'cup', 1),
(4, 'Es Campur', 'Es campur buah dengan sirup', 8000, 18000, 22, 5, 'porsi', 1),
(4, 'Pancake', 'Pancake dengan madu dan mentega', 6000, 15000, 28, 6, 'porsi', 1),

-- Paket
(5, 'Paket Hemat A', 'Nasi goreng + es teh manis', 14000, 27000, 40, 8, 'paket', 1),
(5, 'Paket Hemat B', 'Ayam bakar + nasi + es jeruk', 20000, 38000, 30, 5, 'paket', 1);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('restaurant_name', 'Smart Resto POS', 'Nama restoran'),
('restaurant_address', 'Jl. Merdeka No. 123, Jakarta Pusat', 'Alamat restoran'),
('restaurant_phone', '021-12345678', 'Nomor telepon restoran'),
('restaurant_email', 'info@smartrestopos.com', 'Email restoran'),
('tax_rate', '10', 'Persentase pajak (PB1) dalam persen'),
('points_per_1000', '1', 'Jumlah poin yang didapat per Rp 1.000 belanja'),
('receipt_footer', 'Terima kasih atas kunjungan Anda. Sampai jumpa lagi!', 'Teks footer pada struk'),
('currency', 'Rp', 'Simbol mata uang'),
('receipt_print_auto', '0', 'Auto print struk setelah transaksi (0=tidak, 1=ya)'),
('low_stock_alert', '1', 'Aktifkan notifikasi stok menipis (0=tidak, 1=ya)');

-- --------------------------------------------------------
-- SAMPLE DATA (Optional - untuk testing)
-- --------------------------------------------------------

-- Insert sample member
INSERT INTO members (member_code, member_name, phone, email, address, points, total_spent, join_date, is_active) VALUES
('MBR20250101', 'Budi Santoso', '081234567890', 'budi@example.com', 'Jakarta Selatan', 150, 1500000, '2025-01-01', 1),
('MBR20250102', 'Siti Aminah', '081234567891', 'siti@example.com', 'Jakarta Timur', 85, 850000, '2025-01-02', 1);

-- Note: Sample transactions tidak diinsert agar database fresh untuk production
