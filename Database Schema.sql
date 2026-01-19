-- Smart Resto POS - Database Schema
-- Database: railway (Railway MySQL)
-- Restaurant: Stasiun Kerang

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
CREATE TABLE IF NOT EXISTS `products` (
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
  KEY `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: members
CREATE TABLE IF NOT EXISTS `members` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transactions
CREATE TABLE IF NOT EXISTS `transactions` (
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
  KEY `idx_transactions_date` (`transaction_date`),
  KEY `idx_transactions_user` (`user_id`),
  KEY `fk_transactions_member` (`member_id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaction_items
CREATE TABLE IF NOT EXISTS `transaction_items` (
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
  KEY `idx_transaction_items_transaction` (`transaction_id`),
  KEY `idx_transaction_items_product` (`product_id`),
  CONSTRAINT `fk_transaction_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: stock_history
CREATE TABLE IF NOT EXISTS `stock_history` (
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
  KEY `idx_stock_history_product` (`product_id`),
  KEY `fk_stock_history_transaction` (`transaction_id`),
  KEY `fk_stock_history_user` (`created_by`),
  CONSTRAINT `fk_stock_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_history_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stock_history_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expenses
CREATE TABLE IF NOT EXISTS `expenses` (
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
  KEY `idx_expenses_date` (`expense_date`),
  KEY `fk_expenses_user` (`created_by`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default users (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `is_active`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@stasiunkerang.com', 'admin', 1),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir1@stasiunkerang.com', 'kasir', 1);

-- Insert categories
INSERT INTO `categories` (`category_name`, `description`, `icon`, `is_active`) VALUES
('Gerbong', 'Menu paket gerbong', 'fa-box', 1),
('Kepiting Mix', 'Menu kepiting mix', 'fa-utensils', 1),
('Lobster Mix', 'Menu lobster mix', 'fa-fish', 1),
('Cumi Udang', 'Menu cumi dan udang', 'fa-shrimp', 1),
('Ayam Ceker', 'Menu ayam dan ceker', 'fa-drumstick-bite', 1),
('Kerang Kiloan', 'Menu kerang kiloan', 'fa-clam', 1),
('Sayur', 'Menu sayuran', 'fa-leaf', 1),
('Minuman', 'Menu minuman', 'fa-glass-water', 1),
('Nasi', 'Nasi putih', 'fa-bowl-rice', 1),
('Paket Spesial', 'Paket menu spesial', 'fa-star', 1);

-- Insert products from Stasiun Kerang menu
INSERT INTO `products` (`category_id`, `product_name`, `description`, `cost_price`, `selling_price`, `stock_quantity`, `unit`) VALUES
-- Gerbong
(1, 'Gerbong 1 (3 Varian Kerang)', 'Paket 3 varian kerang', 60000, 130000, 20, 'paket'),
(1, 'Gerbong 2 (4 Varian Kerang)', 'Paket 4 varian kerang', 80000, 160000, 20, 'paket'),
(1, 'Gerbong 3 (5 Varian Kerang)', 'Paket 5 varian kerang', 100000, 180000, 15, 'paket'),

-- Kepiting Mix
(2, 'Kepiting Mix 1 (Kepiting + Kerang 1 varian)', 'Kepiting dengan 1 varian kerang', 50000, 85000, 15, 'porsi'),
(2, 'Kepiting Mix 2 (Kepiting + 2 Varian Kerang)', 'Kepiting dengan 2 varian kerang', 60000, 100000, 15, 'porsi'),
(2, 'Kepiting Mix 3 (Kepiting + 3 Varian Kerang)', 'Kepiting dengan 3 varian kerang', 70000, 120000, 12, 'porsi'),
(2, 'Kepiting Mix 4 (Kepiting + Gurita + Cumi + Udang)', 'Kepiting lengkap dengan seafood', 80000, 140000, 10, 'porsi'),
(2, 'Kepiting Mix 5 (Kepiting + Gurita + Cumi + Udang + 2 Varian Kerang)', 'Kepiting spesial dengan kerang', 95000, 165000, 10, 'porsi'),

-- Lobster Mix
(3, 'Lobster Mix 1 (Lobster + Kerang 1 varian)', 'Lobster dengan 1 varian kerang', 60000, 100000, 10, 'porsi'),
(3, 'Lobster Mix 2 (Lobster + 2 Varian Kerang)', 'Lobster dengan 2 varian kerang', 70000, 120000, 10, 'porsi'),
(3, 'Lobster Mix 3 (Lobster + 3 Varian Kerang)', 'Lobster dengan 3 varian kerang', 80000, 140000, 8, 'porsi'),
(3, 'Lobster Mix 4 (Lobster + Gurita + Cumi + Udang)', 'Lobster lengkap dengan seafood', 95000, 160000, 8, 'porsi'),
(3, 'Lobster Mix 5 (Lobster + Gurita + Cumi + Udang + 2 Varian Kerang)', 'Lobster spesial dengan kerang', 110000, 180000, 5, 'porsi'),

-- Cumi / Udang
(4, 'Cumi Goreng Mentega', 'Cumi goreng dengan saus mentega', 20000, 35000, 30, 'porsi'),
(4, 'Cumi Asam Manis', 'Cumi dengan saus asam manis', 20000, 35000, 30, 'porsi'),
(4, 'Cumi Saus Padang', 'Cumi dengan saus padang pedas', 20000, 35000, 30, 'porsi'),
(4, 'Cumi Saus Tiram', 'Cumi dengan saus tiram', 20000, 35000, 30, 'porsi'),
(4, 'Udang Goreng Mentega', 'Udang goreng dengan saus mentega', 22000, 40000, 25, 'porsi'),
(4, 'Udang Goreng Asam Manis', 'Udang dengan saus asam manis', 22000, 40000, 25, 'porsi'),
(4, 'Udang Goreng Saus Padang', 'Udang dengan saus padang pedas', 22000, 40000, 25, 'porsi'),
(4, 'Udang Goreng Saus Tiram', 'Udang dengan saus tiram', 22000, 40000, 25, 'porsi'),

-- Ayam / Ceker
(5, 'Ceker Balado', 'Ceker ayam dengan sambal balado', 8000, 15000, 50, 'porsi'),
(5, 'Ceker Mentega', 'Ceker ayam dengan saus mentega', 8000, 15000, 50, 'porsi'),
(5, 'Ceker Saus Padang', 'Ceker ayam dengan saus padang', 8000, 15000, 50, 'porsi'),
(5, 'Ceker Asam Manis', 'Ceker ayam dengan saus asam manis', 8000, 15000, 50, 'porsi'),
(5, 'Ceker Pedas Manis', 'Ceker ayam pedas manis', 8000, 15000, 50, 'porsi'),
(5, 'Ceker Lada Hitam', 'Ceker ayam dengan lada hitam', 8000, 15000, 50, 'porsi'),
(5, 'Ayam Goreng Asam Manis', 'Ayam goreng dengan saus asam manis', 10000, 18000, 40, 'porsi'),
(5, 'Ayam Goreng Pedas Manis', 'Ayam goreng pedas manis', 10000, 18000, 40, 'porsi'),
(5, 'Ayam Goreng Mentega', 'Ayam goreng dengan saus mentega', 10000, 18000, 40, 'porsi'),
(5, 'Ayam Saus Padang', 'Ayam dengan saus padang pedas', 10000, 18000, 40, 'porsi'),
(5, 'Ayam Goreng Lada Hitam', 'Ayam goreng dengan lada hitam', 10000, 18000, 40, 'porsi'),

-- Kerang Kiloan
(6, 'Kerang Hijau', 'Kerang hijau segar per kg', 25000, 45000, 50, 'kg'),
(6, 'Kerang Tahu', 'Kerang tahu segar per kg', 28000, 50000, 40, 'kg'),
(6, 'Kerang Dara', 'Kerang dara segar per kg', 30000, 55000, 35, 'kg'),
(6, 'Kerang Batik', 'Kerang batik segar per kg', 35000, 65000, 30, 'kg'),
(6, 'Kerang Simping', 'Kerang simping segar per kg', 40000, 75000, 25, 'kg'),
(6, 'Kerang Bambu', 'Kerang bambu segar per kg', 45000, 80000, 20, 'kg'),
(6, 'Kerang Macan', 'Kerang macan segar per kg', 40000, 75000, 25, 'kg'),

-- Sayur
(7, 'Kangkung Cah Polos', 'Tumis kangkung polos', 5000, 10000, 60, 'porsi'),
(7, 'Kangkung Cah Seafood', 'Tumis kangkung dengan seafood', 8000, 15000, 50, 'porsi'),

-- Minuman
(8, 'Teh Tawar', 'Teh tawar panas/dingin', 1500, 3000, 100, 'gelas'),
(8, 'Teh Botol', 'Teh botol kemasan', 2000, 4000, 80, 'botol'),
(8, 'Teh Manis', 'Teh manis panas/dingin', 2500, 5000, 100, 'gelas'),
(8, 'Teh Lemon', 'Teh lemon segar', 3000, 8000, 60, 'gelas'),
(8, 'Cappucino', 'Cappucino panas', 4000, 8000, 50, 'gelas'),
(8, 'Teh Tarik', 'Teh tarik spesial', 4000, 8000, 50, 'gelas'),
(8, 'Jeruk', 'Jus jeruk segar', 4500, 8000, 50, 'gelas'),
(8, 'Mineral', 'Air mineral botol', 2500, 5000, 100, 'botol'),

-- Nasi
(9, 'Nasi Putih', 'Nasi putih hangat', 2500, 5000, 200, 'porsi'),

-- Paket Spesial  
(10, 'Octopus Porsi', 'Gurita/octopus 1 porsi', 22000, 40000, 15, 'porsi'),
(10, 'Kepiting Porsi', 'Kepiting 1 porsi', 70000, 120000, 10, 'porsi'),
(10, 'Lobster Porsi', 'Lobster 1 porsi', 90000, 130000, 8, 'porsi');

-- Insert settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('restaurant_name', 'Stasiun Kerang', 'Nama restoran'),
('restaurant_address', 'Depok, Jawa Barat', 'Alamat restoran'),
('restaurant_phone', '0812-3456-7890', 'Telepon restoran'),
('restaurant_instagram', '@stasiunkerang', 'Instagram restoran'),
('tax_rate', '10', 'Persentase pajak'),
('points_per_1000', '1', 'Poin per Rp 1.000'),
('receipt_footer', 'Terima kasih atas kunjungan Anda', 'Footer struk'),
('currency', 'Rp', 'Simbol mata uang');
