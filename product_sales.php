<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = "Product Sales Management";
$current_page = "product_sales";

// Include header
require_once 'includes/header.php';
require_once 'includes/db_connection.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product_sale'])) {
    $product_id = $_POST['product_id'];
    $attendant_id = $_POST['attendant_id'];
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $total_amount = $quantity * $unit_price;
    $payment_method = $_POST['payment_method'];

    $sql = "INSERT INTO product_sales (product_id, attendant_id, customer_id, quantity, unit_price, total_amount, payment_method) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiddds", $product_id, $attendant_id, $customer_id, $quantity, $unit_price, $total_amount, $payment_method);
    
    if ($stmt->execute()) {
        // Update product stock
        $update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
        $stock_stmt = $conn->prepare($update_stock);
        $stock_stmt->bind_param("ii", $quantity, $product_id);
        $stock_stmt->execute();
        
        echo "<div class='alert alert-success'>Product sale recorded successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error recording product sale: " . $stmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Sales Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Record Product Sale</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="productSaleForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_id" class="form-label">Product</label>
                                    <select class="form-select" name="product_id" required>
                                        <?php
                                        $products = $conn->query("SELECT * FROM products WHERE stock_quantity > 0");
                                        while ($product = $products->fetch_assoc()) {
                                            echo "<option value='" . $product['product_id'] . "' data-price='" . $product['selling_price'] . "'>" . 
                                                 htmlspecialchars($product['product_name'] . " - Rs. " . $product['selling_price']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="attendant_id" class="form-label">Attendant</label>
                                    <select class="form-select" name="attendant_id" required>
                                        <?php
                                        $attendants = $conn->query("SELECT * FROM attendants WHERE status = 'active'");
                                        while ($attendant = $attendants->fetch_assoc()) {
                                            echo "<option value='" . $attendant['attendant_id'] . "'>" . 
                                                 htmlspecialchars($attendant['name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_id" class="form-label">Customer (Optional)</label>
                                    <select class="form-select" name="customer_id">
                                        <option value="">Select Customer</option>
                                        <?php
                                        $customers = $conn->query("SELECT * FROM customers");
                                        while ($customer = $customers->fetch_assoc()) {
                                            echo "<option value='" . $customer['customer_id'] . "'>" . 
                                                 htmlspecialchars($customer['name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="unit_price" class="form-label">Unit Price</label>
                                    
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="credit">Credit</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="add_product_sale" class="btn btn-primary">Record Product Sale</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Product Sales Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Product Sales</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Attendant</th>
                                        <th>Customer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $product_sales = $conn->query("
                                        SELECT ps.*, p.product_name, a.name as attendant_name,
                                               COALESCE(c.name, 'Walk-in Customer') as customer_name
                                        FROM product_sales ps
                                        JOIN products p ON ps.product_id = p.product_id
                                        JOIN attendants a ON ps.attendant_id = a.attendant_id
                                        LEFT JOIN customers c ON ps.customer_id = c.customer_id
                                        ORDER BY ps.sale_date DESC LIMIT 10
                                    ");
                                    while ($sale = $product_sales->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . date('Y-m-d H:i', strtotime($sale['sale_date'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($sale['product_name']) . "</td>";
                                        echo "<td>" . $sale['quantity'] . "</td>";
                                        echo "<td>Rs. " . number_format($sale['unit_price'], 2) . "</td>";
                                        echo "<td>Rs. " . number_format($sale['total_amount'], 2) . "</td>";
                                        echo "<td>" . ucfirst($sale['payment_method']) . "</td>";
                                        echo "<td>" . htmlspecialchars($sale['attendant_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($sale['customer_name']) . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('select').select2();

            // Auto-fill unit price when product is selected
            $('select[name="product_id"]').change(function() {
                const price = $(this).find(':selected').data('price');
                $('input[name="unit_price"]').val(price);
            });

            // Trigger initial change to set default values
            $('select[name="product_id"]').trigger('change');
        });
    </script>
</body>
</html> 