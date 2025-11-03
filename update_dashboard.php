<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get today's updated stats
$today = date('Y-m-d');
$response = [];

try {
    // Get today's total fuel sales
    $sql = "SELECT COALESCE(SUM((dr.closing_reading - dr.opening_reading) * p.selling_price), 0) as total 
            FROM daily_readings dr
            JOIN fuel_pumps fp ON dr.pump_id = fp.pump_id
            JOIN products p ON fp.product_id = p.product_id 
            WHERE dr.reading_date = ?";
    $stmt = prepare_query($sql, [$today]);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['todaySales'] = $result->fetch_assoc()['total'] ?? 0;

    // Get current fuel stock levels
    $sql = "SELECT SUM(stock_quantity) as total FROM products WHERE product_type = 'fuel'";
    $stmt = prepare_query($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['totalStock'] = $result->fetch_assoc()['total'] ?? 0;

    // Get recent fuel sales
    $sql = "SELECT s.*, p.product_name as fuel_name 
            FROM sales s 
            JOIN products p ON s.product_id = p.product_id 
            WHERE p.product_type = 'fuel'
            ORDER BY s.sale_date DESC LIMIT 5";
    $stmt = prepare_query($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recentSales = [];
    while ($row = $result->fetch_assoc()) {
        $recentSales[] = [
            'date' => date('Y-m-d H:i', strtotime($row['sale_date'])),
            'fuel_name' => htmlspecialchars($row['fuel_name']),
            'quantity' => number_format($row['quantity'], 2),
            'rate' => number_format($row['rate'], 2),
            'total_amount' => number_format($row['total_amount'], 2),
            'payment_method' => ucfirst($row['payment_method']),
            'recorded_by' => htmlspecialchars($row['recorded_by'] ?? 'System')
        ];
    }
    $response['recentSales'] = $recentSales;

    // Format currency for display
    $response['todaySales_formatted'] = format_currency($response['todaySales']);
    $response['totalStock_formatted'] = number_format($response['totalStock'], 2) . ' L';

    echo json_encode($response);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to fetch updated stats']);
    error_log("Dashboard Update Error: " . $e->getMessage());
}
?> 