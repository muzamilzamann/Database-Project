<?php
session_start();
require_once '../config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $product_name = filter_var($_POST['product_name'], FILTER_SANITIZE_STRING);
    $product_type = filter_var($_POST['product_type'], FILTER_SANITIZE_STRING);
    $unit = filter_var($_POST['unit'], FILTER_SANITIZE_STRING);
    $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $purchase_price = filter_var($_POST['purchase_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $selling_price = filter_var($_POST['selling_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Category is only required for non-fuel products
    $category = ($product_type === 'non-fuel') ? 
                filter_var($_POST['category'], FILTER_SANITIZE_STRING) : 
                null;

    try {
        // Prepare the SQL statement
        $sql = "INSERT INTO products (product_name, product_type, category, unit, stock_quantity, purchase_price, selling_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssddd", $product_name, $product_type, $category, $unit, $stock_quantity, $purchase_price, $selling_price);
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Product added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding product.";
        }
        
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
    }
    
    // Redirect back to inventory page
    header('Location: ../inventory.php');
    exit();
} else {
    // If not POST request, redirect to inventory page
    header('Location: ../inventory.php');
    exit();
}
?> 