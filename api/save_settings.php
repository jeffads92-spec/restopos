<?php
/**
 * Smart Resto POS - Save Settings API (FIXED)
 * Admin-only endpoint to save application settings
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $updated_settings = [];
    
    // Process each POST parameter as a setting
    foreach ($_POST as $key => $value) {
        if ($key === 'submit') continue;
        
        // Sanitize key and value
        $setting_key = $conn->real_escape_string($key);
        $setting_value = $conn->real_escape_string($value);
        
        // Check if setting exists
        $check_query = "SELECT setting_id FROM settings WHERE setting_key = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('s', $setting_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing setting
            $update_query = "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
            $stmt2 = $conn->prepare($update_query);
            $stmt2->bind_param('ss', $setting_value, $setting_key);
            $stmt2->execute();
            $stmt2->close();
        } else {
            // Insert new setting
            $insert_query = "INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())";
            $stmt2 = $conn->prepare($insert_query);
            $stmt2->bind_param('ss', $setting_key, $setting_value);
            $stmt2->execute();
            $stmt2->close();
        }
        
        $stmt->close();
        $updated_settings[$key] = $value;
    }
    
    // Log activity
    error_log(sprintf(
        'Settings updated: %d settings changed by Admin #%d',
        count($updated_settings),
        $_SESSION['user_id']
    ));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Pengaturan berhasil disimpan',
        'updated_count' => count($updated_settings)
    ]);
    
} catch (Exception $e) {
    error_log('Save settings error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save settings'
    ]);
}

$conn->close();
?>