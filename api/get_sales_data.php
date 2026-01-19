<?php
/**
 * Smart Resto POS - Get Sales Data API
 * Returns sales data for charts and reports
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/response_helper.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Check authentication
ApiRequest::requireAuth();

try {
    // Get parameters
    $start_date = ApiRequest::getString('start_date', date('Y-m-01'));
    $end_date = ApiRequest::getString('end_date', date('Y-m-d'));
    $group_by = ApiRequest::getString('group_by', 'day'); // day, week, month
    
    // Validate dates
    if (!strtotime($start_date) || !strtotime($end_date)) {
        ApiResponse::error('Format tanggal tidak valid');
    }
    
    // Get daily sales data
    $sales_query = "SELECT 
                        DATE(transaction_date) as date,
                        COUNT(*) as transaction_count,
                        SUM(total_amount) as total_sales,
                        SUM(subtotal) as gross_sales,
                        SUM(tax) as total_tax,
                        SUM(discount) as total_discount
                    FROM transactions
                    WHERE DATE(transaction_date) BETWEEN ? AND ?
                    AND status = 'completed'
                    GROUP BY DATE(transaction_date)
                    ORDER BY date ASC";
    
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
    $sales_data = [];
    $labels = [];
    $totals = [
        'transaction_count' => 0,
        'total_sales' => 0,
        'gross_sales' => 0,
        'total_tax' => 0,
        'total_discount' => 0
    ];
    
    while ($row = $sales_result->fetch_assoc()) {
        $date = $row['date'];
        $sales_data[] = [
            'date' => $date,
            'formatted_date' => date('d M Y', strtotime($date)),
            'transaction_count' => intval($row['transaction_count']),
            'total_sales' => floatval($row['total_sales']),
            'gross_sales' => floatval($row['gross_sales']),
            'total_tax' => floatval($row['total_tax']),
            'total_discount' => floatval($row['total_discount'])
        ];
        
        $labels[] = date('d M', strtotime($date));
        
        $totals['transaction_count'] += intval($row['transaction_count']);
        $totals['total_sales'] += floatval($row['total_sales']);
        $totals['gross_sales'] += floatval($row['gross_sales']);
        $totals['total_tax'] += floatval($row['total_tax']);
        $totals['total_discount'] += floatval($row['total_discount']);
    }
    
    $stmt->close();
    
    // Get payment method breakdown
    $payment_query = "SELECT 
                        payment_method,
                        COUNT(*) as count,
                        SUM(total_amount) as total
                      FROM transactions
                      WHERE DATE(transaction_date) BETWEEN ? AND ?
                      AND status = 'completed'
                      GROUP BY payment_method";
    
    $stmt2 = $conn->prepare($payment_query);
    $stmt2->bind_param('ss', $start_date, $end_date);
    $stmt2->execute();
    $payment_result = $stmt2->get_result();
    
    $payment_data = [];
    while ($row = $payment_result->fetch_assoc()) {
        $payment_data[] = [
            'method' => strtoupper($row['payment_method']),
            'count' => intval($row['count']),
            'total' => floatval($row['total']),
            'percentage' => $totals['total_sales'] > 0 ? round((floatval($row['total']) / $totals['total_sales']) * 100, 2) : 0
        ];
    }
    
    $stmt2->close();
    
    // Get top selling products
    $top_products_query = "SELECT 
                            p.product_name,
                            SUM(ti.quantity) as total_qty,
                            SUM(ti.subtotal) as total_revenue
                          FROM transaction_items ti
                          JOIN products p ON ti.product_id = p.product_id
                          JOIN transactions t ON ti.transaction_id = t.transaction_id
                          WHERE DATE(t.transaction_date) BETWEEN ? AND ?
                          AND t.status = 'completed'
                          GROUP BY p.product_id
                          ORDER BY total_revenue DESC
                          LIMIT 10";
    
    $stmt3 = $conn->prepare($top_products_query);
    $stmt3->bind_param('ss', $start_date, $end_date);
    $stmt3->execute();
    $products_result = $stmt3->get_result();
    
    $top_products = [];
    while ($row = $products_result->fetch_assoc()) {
        $top_products[] = [
            'product_name' => $row['product_name'],
            'total_qty' => intval($row['total_qty']),
            'total_revenue' => floatval($row['total_revenue'])
        ];
    }
    
    $stmt3->close();
    
    // Get category breakdown
    $category_query = "SELECT 
                        c.category_name,
                        SUM(ti.quantity) as total_qty,
                        SUM(ti.subtotal) as total_revenue
                      FROM transaction_items ti
                      JOIN products p ON ti.product_id = p.product_id
                      JOIN categories c ON p.category_id = c.category_id
                      JOIN transactions t ON ti.transaction_id = t.transaction_id
                      WHERE DATE(t.transaction_date) BETWEEN ? AND ?
                      AND t.status = 'completed'
                      GROUP BY c.category_id
                      ORDER BY total_revenue DESC";
    
    $stmt4 = $conn->prepare($category_query);
    $stmt4->bind_param('ss', $start_date, $end_date);
    $stmt4->execute();
    $category_result = $stmt4->get_result();
    
    $category_data = [];
    while ($row = $category_result->fetch_assoc()) {
        $category_data[] = [
            'category_name' => $row['category_name'],
            'total_qty' => intval($row['total_qty']),
            'total_revenue' => floatval($row['total_revenue'])
        ];
    }
    
    $stmt4->close();
    
    // Send response
    ApiResponse::success('Data berhasil diambil', [
        'period' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days' => count($sales_data)
        ],
        'summary' => $totals,
        'daily_sales' => $sales_data,
        'labels' => $labels,
        'payment_methods' => $payment_data,
        'top_products' => $top_products,
        'categories' => $category_data
    ]);
    
} catch (Exception $e) {
    error_log('Get sales data error: ' . $e->getMessage());
    ApiResponse::serverError('Gagal mengambil data: ' . $e->getMessage());
}

$conn->close();
?>