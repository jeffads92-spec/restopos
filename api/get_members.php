<?php
/**
 * Smart Resto POS - Get Members API
 * Returns member list for POS dropdown
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $is_active = isset($_GET['is_active']) ? intval($_GET['is_active']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    
    // Build query
    $query = "SELECT 
                member_id,
                member_code,
                member_name,
                phone,
                email,
                points,
                total_spent,
                is_active,
                join_date
              FROM members
              WHERE is_active = ?";
    
    $params = [$is_active];
    $types = "i";
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (member_name LIKE ? OR member_code LIKE ? OR phone LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    $query .= " ORDER BY member_name ASC LIMIT ?";
    $params[] = $limit;
    $types .= "i";
    
    // Prepare and execute
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    // Bind parameters dynamically
    if (!empty($params)) {
        $bind_params = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $members = [];
    
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'member_id' => intval($row['member_id']),
            'member_code' => $row['member_code'],
            'member_name' => $row['member_name'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'points' => intval($row['points']),
            'total_spent' => floatval($row['total_spent']),
            'is_active' => intval($row['is_active']),
            'join_date' => $row['join_date'],
            'display_text' => $row['member_name'] . ' - ' . $row['member_code'] . ' (' . number_format($row['points']) . ' pts)'
        ];
    }
    
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'members' => $members,
        'total' => count($members),
        'search' => $search
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get members error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve members: ' . $e->getMessage(),
        'members' => []
    ]);
}

$conn->close();
?>