<?php
/**
 * Smart Resto POS - API Response Helper
 * Centralized response handling for consistent API responses
 */

class ApiResponse {
    
    /**
     * Send success response
     */
    public static function success($message, $data = [], $http_code = 200) {
        http_response_code($http_code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send error response
     */
    public static function error($message, $http_code = 400, $errors = []) {
        http_response_code($http_code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send unauthorized response (401)
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response (403)
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send not found response (404)
     */
    public static function notFound($resource = 'Resource', $message = null) {
        $msg = $message ?? "{$resource} not found";
        self::error($msg, 404);
    }
    
    /**
     * Send validation error response (422)
     */
    public static function validationError($errors = [], $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send server error response (500)
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send method not allowed response (405)
     */
    public static function methodNotAllowed($allowed_methods = []) {
        $message = 'Method not allowed';
        if (!empty($allowed_methods)) {
            $message .= '. Allowed: ' . implode(', ', $allowed_methods);
        }
        self::error($message, 405);
    }
}

/**
 * API Request Helper
 */
class ApiRequest {
    
    /**
     * Get JSON input from request body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('Invalid JSON: ' . json_last_error_msg(), 400);
        }
        
        return $data;
    }
    
    /**
     * Check if request method matches
     */
    public static function requireMethod($method) {
        $current_method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        if (is_array($method)) {
            if (!in_array($current_method, $method)) {
                ApiResponse::methodNotAllowed($method);
            }
        } else {
            if (strtoupper($current_method) !== strtoupper($method)) {
                ApiResponse::methodNotAllowed([$method]);
            }
        }
    }
    
    /**
     * Check authentication
     */
    public static function requireAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            ApiResponse::unauthorized();
        }
    }
    
    /**
     * Check admin role
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            ApiResponse::forbidden('Admin access required');
        }
    }
    
    /**
     * Get parameter with default value
     */
    public static function get($key, $default = null, $source = 'GET') {
        $data = $source === 'POST' ? $_POST : $_GET;
        return isset($data[$key]) ? $data[$key] : $default;
    }
    
    /**
     * Get integer parameter
     */
    public static function getInt($key, $default = 0, $source = 'GET') {
        $value = self::get($key, $default, $source);
        return intval($value);
    }
    
    /**
     * Get float parameter
     */
    public static function getFloat($key, $default = 0.0, $source = 'GET') {
        $value = self::get($key, $default, $source);
        return floatval($value);
    }
    
    /**
     * Get boolean parameter
     */
    public static function getBool($key, $default = false, $source = 'GET') {
        $value = self::get($key, $default, $source);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get trimmed string parameter
     */
    public static function getString($key, $default = '', $source = 'GET') {
        $value = self::get($key, $default, $source);
        return trim($value);
    }
}

/**
 * Database Helper for API
 */
class DbHelper {
    
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Execute prepared statement safely
     */
    public function execute($query, $params = [], $types = '') {
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            ApiResponse::serverError('Database error');
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
            ApiResponse::serverError('Database error');
        }
        
        return $stmt;
    }
    
    /**
     * Get single row
     */
    public function getRow($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }
    
    /**
     * Get all rows
     */
    public function getAll($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
    
    /**
     * Get count
     */
    public function getCount($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return intval(reset($row));
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if (!$this->conn->begin_transaction()) {
            ApiResponse::serverError('Failed to start transaction');
        }
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if (!$this->conn->commit()) {
            ApiResponse::serverError('Failed to commit transaction');
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
}
?>