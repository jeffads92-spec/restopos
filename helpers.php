<?php
/**
 * Smart Resto POS - Helper Functions
 * Collection of utility functions for the application
 * COMPLETE VERSION WITH ALL FIXES
 */

/**
 * Get product image with fallback
 * @param string $image_name - Image filename
 * @param string $size - Image size (not used currently)
 * @return string - Image path or fallback
 */
function get_product_image($image_name, $size = 'medium') {
    if (empty($image_name)) {
        return 'assets/images/no-image.png';
    }
    
    $upload_path = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/uploads/products/';
    $upload_url = defined('UPLOAD_URL') ? UPLOAD_URL : 'uploads/products/';
    $image_path = $upload_path . $image_name;
    
    // Check if file exists
    if (file_exists($image_path)) {
        return $upload_url . $image_name;
    }
    
    return 'assets/images/no-image.png';
}

/**
 * Format currency to IDR
 * @param float $amount - Amount to format
 * @param string $prefix - Currency prefix
 * @return string - Formatted currency
 */
function format_rupiah($amount, $prefix = 'Rp ') {
    $number = typeof($amount) === 'string' ? floatval($amount) : $amount;
    if (is_nan($number)) return $prefix . '0';
    
    $number_string = number_format($number, 0, ',', '.');
    return $prefix . $number_string;
}

/**
 * Format date Indonesia
 * @param string $date - Date string
 * @param string $format - Date format
 * @return string - Formatted date
 */
function format_date($date, $format = 'd F Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $days = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return '-';
    }
    
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $day_name = $days[date('l', $timestamp)];
    
    if ($format == 'd F Y') {
        return $day . ' ' . $month . ' ' . $year;
    } elseif ($format == 'l, d F Y') {
        return $day_name . ', ' . $day . ' ' . $month . ' ' . $year;
    } else {
        return date($format, $timestamp);
    }
}

/**
 * Format datetime
 * @param string $datetime - DateTime string
 * @return string - Formatted datetime
 */
function format_datetime($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return format_date($datetime, 'd F Y') . ' ' . date('H:i', strtotime($datetime));
}

/**
 * Generate invoice number
 * @param string $prefix - Invoice prefix
 * @return string - Generated invoice number
 */
function generate_invoice($prefix = 'INV') {
    return $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Sanitize input
 * @param string $data - Input data
 * @return string - Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Upload image with validation
 * @param array $file - $_FILES array
 * @param string $destination - Upload destination folder
 * @return array - Upload result
 */
function upload_image($file, $destination = null) {
    if ($destination === null) {
        $destination = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/uploads/products/';
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 5242880; // 5MB
    
    // Check for upload errors
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension'
        ];
        
        $error = isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE;
        $message = $error_messages[$error] ?? 'Error upload file: ' . $error;
        
        return [
            'success' => false, 
            'message' => $message
        ];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return [
            'success' => false, 
            'message' => 'File terlalu besar (max ' . ($max_size / 1048576) . 'MB)'
        ];
    }
    
    // Get file extension
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate extension
    if (!in_array($ext, $allowed_types)) {
        return [
            'success' => false, 
            'message' => 'Format file tidak didukung. Hanya: ' . implode(', ', $allowed_types)
        ];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'image/jpeg', 
        'image/png', 
        'image/gif'
    ];
    
    if (!in_array($mime, $allowed_mimes)) {
        return [
            'success' => false,
            'message' => 'Tipe file tidak valid'
        ];
    }
    
    // Create unique filename
    $new_filename = uniqid('prod_') . '_' . time() . '.' . $ext;
    $upload_path = $destination . $new_filename;
    
    // Create directory if not exists
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0777, true)) {
            return [
                'success' => false,
                'message' => 'Gagal membuat folder upload'
            ];
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Set proper permissions
        chmod($upload_path, 0644);
        
        return [
            'success' => true, 
            'filename' => $new_filename, 
            'path' => $upload_path
        ];
    }
    
    return [
        'success' => false, 
        'message' => 'Gagal upload file'
    ];
}

/**
 * Delete image
 * @param string $filename - Image filename
 * @param string $directory - Image directory
 * @return bool - Success status
 */
function delete_image($filename, $directory = null) {
    if (empty($filename)) {
        return false;
    }
    
    if ($directory === null) {
        $directory = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/uploads/products/';
    }
    
    $file_path = $directory . $filename;
    
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

/**
 * Validate uploaded image
 * @param array $file - File from $_FILES
 * @param int $max_size - Maximum file size in bytes
 * @return array - Validation result
 */
function validate_image_upload($file, $max_size = 5242880) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'message' => 'No file uploaded', 'file' => null];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Format tidak didukung. Gunakan: ' . implode(', ', $allowed)];
    }
    
    if ($file['size'] > $max_size) {
        $max_mb = $max_size / 1048576;
        return ['success' => false, 'message' => "File terlalu besar (max {$max_mb}MB)"];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'message' => 'Tipe file tidak valid'];
    }
    
    return ['success' => true, 'extension' => $ext, 'mime' => $mime];
}

/**
 * Check if user has permission
 * @param string $permission - Permission name
 * @return bool - Has permission or not
 */
function hasPermission($permission) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Define role-based permissions
    $permissions = [
        'admin' => ['*'], // Admin has all permissions
        'kasir' => [
            'view_products', 
            'create_transaction', 
            'view_transactions',
            'view_members',
            'view_reports'
        ]
    ];
    
    $user_permissions = $permissions[$_SESSION['role']] ?? [];
    
    // Check if user has all permissions or specific permission
    return in_array('*', $user_permissions) || in_array($permission, $user_permissions);
}

/**
 * Log activity to file
 * @param string $message - Log message
 * @param string $level - Log level (info, warning, error)
 * @return void
 */
function log_activity($message, $level = 'info') {
    $log_dir = __DIR__ . '/logs';
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $log_dir . '/activity_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_entry = "[{$timestamp}] [{$level}] [{$user}] [{$ip}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Generate secure random string
 * @param int $length - Length of string
 * @return string - Random string
 */
function generate_random_string($length = 32) {
    try {
        $bytes = random_bytes($length);
        return bin2hex($bytes);
    } catch (Exception $e) {
        // Fallback to less secure method
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
}

/**
 * Validate email format
 * @param string $email - Email to validate
 * @return bool - Valid or not
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesia format)
 * @param string $phone - Phone number
 * @return bool - Valid or not
 */
function is_valid_phone($phone) {
    // Remove non-numeric characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Indonesian phone numbers are typically 10-15 digits
    return strlen($clean) >= 10 && strlen($clean) <= 15;
}

/**
 * Clean phone number
 * @param string $phone - Phone number
 * @return string - Cleaned phone number
 */
function clean_phone($phone) {
    // Remove all non-numeric characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert 08xx to 628xx for international format
    if (substr($clean, 0, 1) === '0') {
        $clean = '62' . substr($clean, 1);
    }
    
    return $clean;
}

/**
 * Calculate points from amount
 * @param float $amount - Transaction amount
 * @return int - Points earned
 */
function calculate_points($amount) {
    $points_per_thousand = defined('POINTS_PER_1000') ? intval(POINTS_PER_1000) : 1;
    return floor($amount / 1000) * $points_per_thousand;
}

/**
 * Get time ago string
 * @param string $datetime - DateTime string
 * @return string - Time ago representation
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return format_date($datetime);
    }
}

/**
 * Format file size
 * @param int $bytes - Size in bytes
 * @return string - Formatted size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if request is AJAX
 * @return bool
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get client IP address
 * @return string
 */
function get_client_ip() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/**
 * Generate barcode/QR code URL
 * @param string $text - Text to encode
 * @param string $type - Type (qr or barcode)
 * @return string - Image URL
 */
function generate_code_url($text, $type = 'qr') {
    if ($type === 'qr') {
        // Using Google Charts API for QR code
        return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($text);
    } else {
        // Using barcode generator
        return 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($text) . '&code=Code128';
    }
}

/**
 * Truncate text with ellipsis
 * @param string $text - Text to truncate
 * @param int $length - Maximum length
 * @param string $suffix - Suffix to add
 * @return string - Truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Array to CSV download
 * @param array $data - Data array
 * @param string $filename - Filename
 * @return void
 */
function array_to_csv_download($data, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Add UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Add header row
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}
?>