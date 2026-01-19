<?php
/**
 * Smart Resto POS - Validation Helper
 * Centralized validation functions for API endpoints
 */

class Validator {
    
    /**
     * Validate required fields
     */
    public static function required($value, $field_name) {
        if (empty($value) && $value !== '0' && $value !== 0) {
            throw new Exception("{$field_name} wajib diisi");
        }
        return $value;
    }
    
    /**
     * Validate email format
     */
    public static function email($email, $required = true) {
        if (empty($email) && !$required) {
            return $email;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid');
        }
        return $email;
    }
    
    /**
     * Validate phone number
     */
    public static function phone($phone, $required = false) {
        if (empty($phone) && !$required) {
            return $phone;
        }
        
        // Remove non-numeric characters
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($clean_phone) < 10 || strlen($clean_phone) > 15) {
            throw new Exception('Nomor telepon harus 10-15 digit');
        }
        
        return $clean_phone;
    }
    
    /**
     * Validate integer
     */
    public static function integer($value, $field_name, $min = null, $max = null) {
        $int_value = intval($value);
        
        if ($min !== null && $int_value < $min) {
            throw new Exception("{$field_name} minimal {$min}");
        }
        
        if ($max !== null && $int_value > $max) {
            throw new Exception("{$field_name} maksimal {$max}");
        }
        
        return $int_value;
    }
    
    /**
     * Validate float/decimal
     */
    public static function decimal($value, $field_name, $min = null, $max = null) {
        $float_value = floatval($value);
        
        if ($min !== null && $float_value < $min) {
            throw new Exception("{$field_name} minimal {$min}");
        }
        
        if ($max !== null && $float_value > $max) {
            throw new Exception("{$field_name} maksimal {$max}");
        }
        
        return $float_value;
    }
    
    /**
     * Validate string length
     */
    public static function string($value, $field_name, $min = null, $max = null) {
        $trimmed = trim($value);
        $length = strlen($trimmed);
        
        if ($min !== null && $length < $min) {
            throw new Exception("{$field_name} minimal {$min} karakter");
        }
        
        if ($max !== null && $length > $max) {
            throw new Exception("{$field_name} maksimal {$max} karakter");
        }
        
        return $trimmed;
    }
    
    /**
     * Validate username format
     */
    public static function username($username) {
        $trimmed = trim($username);
        
        if (strlen($trimmed) < 3) {
            throw new Exception('Username minimal 3 karakter');
        }
        
        if (strlen($trimmed) > 50) {
            throw new Exception('Username maksimal 50 karakter');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $trimmed)) {
            throw new Exception('Username hanya boleh huruf, angka, dan underscore');
        }
        
        return $trimmed;
    }
    
    /**
     * Validate password strength
     */
    public static function password($password, $min_length = 6) {
        if (strlen($password) < $min_length) {
            throw new Exception("Password minimal {$min_length} karakter");
        }
        
        return $password;
    }
    
    /**
     * Validate enum values
     */
    public static function enum($value, $field_name, $allowed_values) {
        if (!in_array($value, $allowed_values)) {
            $allowed_list = implode(', ', $allowed_values);
            throw new Exception("{$field_name} harus salah satu dari: {$allowed_list}");
        }
        
        return $value;
    }
    
    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            throw new Exception('Format tanggal tidak valid');
        }
        
        return $date;
    }
    
    /**
     * Validate datetime format
     */
    public static function datetime($datetime) {
        if (!strtotime($datetime)) {
            throw new Exception('Format tanggal/waktu tidak valid');
        }
        
        return $datetime;
    }
    
    /**
     * Sanitize HTML to prevent XSS
     */
    public static function sanitizeHtml($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate and sanitize SQL string
     */
    public static function sqlString($value, $conn) {
        return $conn->real_escape_string(trim($value));
    }
    
    /**
     * Validate image upload
     */
    public static function image($file, $max_size = 5242880) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error upload file');
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Format gambar tidak didukung (gunakan JPG, PNG, atau GIF)');
        }
        
        if ($file['size'] > $max_size) {
            $max_mb = $max_size / 1048576;
            throw new Exception("Ukuran gambar terlalu besar (max {$max_mb}MB)");
        }
        
        return $file;
    }
    
    /**
     * Validate JSON data
     */
    public static function json($json_string) {
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }
}

/**
 * API Response Helper
 */
class ApiResponse {
    
    public static function success($message, $data = []) {
        return json_encode(array_merge([
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $data));
    }
    
    public static function error($message, $code = 400) {
        http_response_code($code);
        return json_encode([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function unauthorized() {
        http_response_code(401);
        return json_encode([
            'success' => false,
            'message' => 'Unauthorized access',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function forbidden() {
        http_response_code(403);
        return json_encode([
            'success' => false,
            'message' => 'Access forbidden',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function notFound($resource = 'Resource') {
        http_response_code(404);
        return json_encode([
            'success' => false,
            'message' => "{$resource} not found",
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
?>