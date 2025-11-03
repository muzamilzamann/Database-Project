<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "petrol_pump_system");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$type = $_GET['type'] ?? '';

if ($type) {
    $stmt = $conn->prepare("SELECT product_id, product_name, selling_price FROM products WHERE product_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode($products);
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Product type not specified']);
}

$conn->close();
?> 