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
    $purchase_date = $_POST['purchase_date'];
    $supplier_id = filter_var($_POST['supplier_id'], FILTER_SANITIZE_NUMBER_INT);
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $price_per_unit = filter_var($_POST['price_per_unit'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $total_amount = filter_var($_POST['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
        // Prepare the SQL statement
        $sql = "INSERT INTO purchases (purchase_date, supplier_id, product_id, quantity, price_per_unit, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiddd", $purchase_date, $supplier_id, $product_id, $quantity, $price_per_unit, $total_amount);
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Purchase record added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding purchase record.";
        }
        
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
    }
    
    // Redirect back to purchases page
    header('Location: ../purchases.php');
    exit();
} else {
    // If not POST request, redirect to purchases page
    header('Location: ../purchases.php');
    exit();
}
?> 