<?php
session_start();
include 'includes/header.php';

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');

// Database connection
$conn = new mysqli("localhost", "root", "", "petrol_pump_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for new sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sale'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $pump_id = $_POST['pump_id'] ?: NULL;
        $product_id = $_POST['product_id'];
        $customer_id = $_POST['customer_id'] ?: NULL;
        $quantity = $_POST['quantity'];
        $rate = $_POST['rate'];
        $total_amount = $quantity * $rate;
        $payment_method = 'cash';
        $recorded_by = $_POST['recorded_by'];

        // Check if there's enough stock
        $check_stock_sql = "SELECT stock_quantity FROM products WHERE product_id = ?";
        $check_stmt = $conn->prepare($check_stock_sql);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product || $product['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock available!");
        }

        // Insert sale record
        $sql = "INSERT INTO sales (pump_id, product_id, customer_id, quantity, rate, total_amount, payment_method, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidddss", $pump_id, $product_id, $customer_id, $quantity, $rate, $total_amount, $payment_method, $recorded_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding sale record: " . $stmt->error);
        }
        
        // Update product stock
        $update_stock_sql = "UPDATE products SET 
                            stock_quantity = stock_quantity - ?
                            WHERE product_id = ?";
                               
        $update_stmt = $conn->prepare($update_stock_sql);
        $update_stmt->bind_param("di", $quantity, $product_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating stock: " . $update_stmt->error);
        }

        // If everything is successful, commit the transaction
        $conn->commit();
        set_alert('success', 'Sale record added and stock updated successfully!');
        
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $conn->rollback();
        set_alert('danger', $e->getMessage());
    }

    // Close all statements
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit();
}

// Handle delete sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_sale'])) {
    $sale_id = $_POST['sale_id'];
    
    $delete_sql = "DELETE FROM sales WHERE sale_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $sale_id);
    
    if ($stmt->execute()) {
        set_alert('success', 'Sale record deleted successfully!');
    } else {
        set_alert('danger', 'Error deleting sale record: ' . $stmt->error);
    }
    $stmt->close();
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit();
}

// Handle edit sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_sale'])) {
    $sale_id = $_POST['sale_id'];
    $pump_id = $_POST['pump_id'] ?: NULL;
    $product_id = $_POST['product_id'];
    $customer_id = $_POST['customer_id'] ?: NULL;
    $quantity = $_POST['quantity'];
    $rate = $_POST['rate'];
    $total_amount = $quantity * $rate;
    $payment_method = $_POST['payment_method'];
    $recorded_by = $_POST['recorded_by'];

    $update_sql = "UPDATE sales SET pump_id = ?, product_id = ?, customer_id = ?, 
                   quantity = ?, rate = ?, total_amount = ?, payment_method = ?, 
                   recorded_by = ? WHERE sale_id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("iiidddsssi", $pump_id, $product_id, $customer_id, $quantity, 
                      $rate, $total_amount, $payment_method, $recorded_by, $sale_id);
    
    if ($stmt->execute()) {
        set_alert('success', 'Sale record updated successfully!');
    } else {
        set_alert('danger', 'Error updating sale record: ' . $stmt->error);
    }
    $stmt->close();
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit();
}

// Function to generate sales table
function generateSalesTable($rows) {
    if (empty($rows)) {
        echo "<div class='alert alert-info'>No sales records found.</div>";
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table custom-table">
            <thead>
                <tr>
                    <th>SALE ID</th>
                    <th>PUMP</th>
                    <th>PRODUCT</th>
                    <th>CUSTOMER</th>
                    <th>QUANTITY</th>
                    <th>RATE</th>
                    <th>TOTAL AMOUNT</th>
                    <th>PAYMENT</th>
                    <th>DATE</th>
                    <th>RECORDED BY</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo $row['sale_id']; ?></td>
                    <td><?php echo $row['pump_name'] ?? 'N/A'; ?></td>
                    <td><?php echo $row['product_name']; ?></td>
                    <td><?php echo $row['customer_name'] ?? 'Walk-in Customer'; ?></td>
                    <td><?php echo number_format($row['quantity'], 2) . ' ' . $row['unit']; ?></td>
                    <td>Rs. <?php echo number_format($row['rate'], 2); ?></td>
                    <td>Rs. <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><span class="payment-badge <?php echo strtolower($row['payment_method']); ?>"><?php echo ucfirst($row['payment_method']); ?></span></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['sale_date'])); ?></td>
                    <td><?php echo $row['recorded_by']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-sale" data-bs-toggle="modal" data-bs-target="#editSaleModal" 
                            data-sale='<?php echo json_encode($row); ?>'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-sale" data-sale-id="<?php echo $row['sale_id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="salesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="fuel-tab" data-bs-toggle="tab" data-bs-target="#fuel" type="button" role="tab" aria-controls="fuel" aria-selected="true">
                        <i class="fas fa-gas-pump me-2"></i>Fuel Sales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                        <i class="fas fa-box me-2"></i>Non-Fuel Sales
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    <i class="fas fa-plus me-2"></i>Add New Sale
                </button>
            </div>
            
            <div class="tab-content" id="salesTabContent">
                <!-- Fuel Sales Tab -->
                <div class="tab-pane fade show active" id="fuel" role="tabpanel" aria-labelledby="fuel-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Pump</th>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Quantity (L)</th>
                                    <th>Rate</th>
                                    <th>Total Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Recorded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $fuel_sql = "SELECT s.*, p.pump_name, pr.product_name, pr.unit, c.name as customer_name 
                                            FROM sales s 
                                            LEFT JOIN fuel_pumps p ON s.pump_id = p.pump_id 
                                            LEFT JOIN products pr ON s.product_id = pr.product_id 
                                            LEFT JOIN customers c ON s.customer_id = c.customer_id 
                                            WHERE pr.product_type = 'fuel'
                                            ORDER BY s.sale_date DESC";
                                $fuel_result = $conn->query($fuel_sql);
                                while ($row = $fuel_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['sale_id'] . "</td>";
                                    echo "<td>" . ($row['pump_name'] ?? 'N/A') . "</td>";
                                    echo "<td>" . $row['product_name'] . "</td>";
                                    echo "<td>" . ($row['customer_name'] ?? 'Walk-in Customer') . "</td>";
                                    echo "<td>" . number_format($row['quantity'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($row['rate'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($row['total_amount'], 2) . "</td>";
                                    echo "<td><span class='badge payment-badge " . strtolower($row['payment_method']) . "'>" . 
                                         ucfirst($row['payment_method']) . "</span></td>";
                                    echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
                                    echo "<td>" . $row['recorded_by'] . "</td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-primary edit-sale' data-bs-toggle='modal' 
                                                    data-bs-target='#editSaleModal' data-sale='" . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                            <button class='btn btn-sm btn-danger delete-sale' data-sale-id='" . $row['sale_id'] . "'>
                                                <i class='fas fa-trash'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Non-Fuel Sales Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <div class="mb-4">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="saleSearch" class="form-control search-input" placeholder="Search sales...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Total Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Recorded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="nonFuelSalesTableBody">
                                <?php
                                $non_fuel_sql = "SELECT s.*, pr.product_name, pr.unit, c.name as customer_name 
                                                FROM sales s 
                                                LEFT JOIN products pr ON s.product_id = pr.product_id 
                                                LEFT JOIN customers c ON s.customer_id = c.customer_id 
                                                WHERE pr.product_type = 'non-fuel'
                                                ORDER BY s.sale_date DESC";
                                $non_fuel_result = $conn->query($non_fuel_sql);
                                while ($row = $non_fuel_result->fetch_assoc()) {
                                    echo "<tr class='sale-row'>";
                                    echo "<td>" . $row['sale_id'] . "</td>";
                                    echo "<td class='product-name'>" . $row['product_name'] . "</td>";
                                    echo "<td>" . ($row['customer_name'] ?? 'Walk-in Customer') . "</td>";
                                    echo "<td>" . number_format($row['quantity'], 2) . " " . $row['unit'] . "</td>";
                                    echo "<td>Rs. " . number_format($row['rate'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($row['total_amount'], 2) . "</td>";
                                    echo "<td><span class='badge payment-badge " . strtolower($row['payment_method']) . "'>" . 
                                         ucfirst($row['payment_method']) . "</span></td>";
                                    echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
                                    echo "<td>" . $row['recorded_by'] . "</td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-primary edit-sale' data-bs-toggle='modal' 
                                                    data-bs-target='#editSaleModal' data-sale='" . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                            <button class='btn btn-sm btn-danger delete-sale' data-sale-id='" . $row['sale_id'] . "'>
                                                <i class='fas fa-trash'></i>
                                            </button>
                                          </td>";
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

<!-- Add Sale Modal -->
<div class="modal fade custom-modal" id="addSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="saleForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Type*</label>
                            <select name="product_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="fuel">Fuel</option>
                                <option value="non-fuel">Non-Fuel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product*</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pump</label>
                            <select name="pump_id" class="form-select">
                                <option value="">Select Pump</option>
                                <?php
                                $pumps = $conn->query("SELECT * FROM fuel_pumps WHERE status = 'active'");
                                while ($pump = $pumps->fetch_assoc()) {
                                    echo "<option value='" . $pump['pump_id'] . "'>" . $pump['pump_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">Customer</option>
                                <?php
                                $customers = $conn->query("SELECT * FROM customers");
                                while ($customer = $customers->fetch_assoc()) {
                                    echo "<option value='" . $customer['customer_id'] . "'>" . $customer['name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantity*</label>
                            <input type="number" name="quantity" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rate*</label>
                            <input type="number" name="rate" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input type="number" name="total_amount" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Method*</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recorded By*</label>
                            <input type="text" name="recorded_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly required>
                        </div>
                    </div>
                    <input type="hidden" name="add_sale" value="1">
                    <button type="submit" class="btn btn-primary">Save Sale</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade custom-modal" id="editSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editSaleForm">
                    <input type="hidden" name="edit_sale" value="1">
                    <input type="hidden" name="sale_id" id="edit_sale_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Type*</label>
                            <select name="product_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="fuel">Fuel</option>
                                <option value="non-fuel">Non-Fuel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product*</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pump</label>
                            <select name="pump_id" class="form-select">
                                <option value="">Select Pump</option>
                                <?php
                                $pumps = $conn->query("SELECT * FROM fuel_pumps WHERE status = 'active'");
                                while ($pump = $pumps->fetch_assoc()) {
                                    echo "<option value='" . $pump['pump_id'] . "'>" . $pump['pump_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">Walk-in Customer</option>
                                <?php
                                $customers = $conn->query("SELECT * FROM customers");
                                while ($customer = $customers->fetch_assoc()) {
                                    echo "<option value='" . $customer['customer_id'] . "'>" . $customer['name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantity*</label>
                            <input type="number" name="quantity" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rate*</label>
                            <input type="number" name="rate" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input type="number" name="total_amount" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Method*</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recorded By*</label>
                            <input type="text" name="recorded_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Sale</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Tab Styles */
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    border: none;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: #fff;
    border: none;
    border-bottom: 3px solid #0d6efd;
}

/* Table Styles */
.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.table thead {
    position: sticky;
    top: 0;
    z-index: 1;
}

/* Search Styles */
.search-container {
    position: relative;
    max-width: 500px;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    font-size: 1rem;
    width: 100%;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Payment Badge Styles */
.payment-badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-weight: 500;
}

.payment-badge.cash {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.payment-badge.card {
    background-color: #e3f2fd;
    color: #1565c0;
}

.payment-badge.credit {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

/* Action Button Styles */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 2px;
}

.btn-sm i {
    font-size: 0.875rem;
}

/* No Results Message */
.no-results {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Auto-calculate total amount
    $('input[name="quantity"], input[name="rate"]').on('input', function() {
        var quantity = $('input[name="quantity"]').val() || 0;
        var rate = $('input[name="rate"]').val() || 0;
        var total = (quantity * rate).toFixed(2);
        $('input[name="total_amount"]').val(total);
    });

    // Handle product type selection
    $('select[name="product_type"]').on('change', function() {
        var productType = $(this).val();
        var productSelect = $('select[name="product_id"]');
        var pumpSelect = $('select[name="pump_id"]');
        
        // Clear product dropdown
        productSelect.html('<option value="">Select Product</option>');
        
        if (productType) {
            // Show/hide pump selection based on product type
            if (productType === 'fuel') {
                pumpSelect.closest('.col-md-6').show();
            } else {
                pumpSelect.closest('.col-md-6').hide();
                pumpSelect.val('');
            }
            
            // Load products based on type
            $.get('get_products.php', { type: productType }, function(products) {
                products.forEach(function(product) {
                    productSelect.append(
                        $('<option></option>')
                            .val(product.product_id)
                            .text(product.product_name + ' (Rs. ' + product.selling_price + ')')
                            .data('price', product.selling_price)
                    );
                });
            });
        }
    });

    // Auto-fill rate when product is selected
    $('select[name="product_id"]').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var price = selectedOption.data('price');
        if (price) {
            $('input[name="rate"]').val(price);
            $('input[name="quantity"]').trigger('input');
        }
    });

    // Handle Edit Sale Button Click
    $('.edit-sale').on('click', function() {
        var saleData = $(this).data('sale');
        $('#edit_sale_id').val(saleData.sale_id);
        
        // Set product type based on the product
        var productType = saleData.product_type || 'fuel';
        $('select[name="product_type"]').val(productType).trigger('change');
        
        // Set other form values
        setTimeout(function() {
            $('select[name="product_id"]').val(saleData.product_id);
            $('select[name="pump_id"]').val(saleData.pump_id);
            $('select[name="customer_id"]').val(saleData.customer_id);
            $('input[name="quantity"]').val(saleData.quantity);
            $('input[name="rate"]').val(saleData.rate);
            $('input[name="total_amount"]').val(saleData.total_amount);
            $('select[name="payment_method"]').val(saleData.payment_method);
            $('input[name="recorded_by"]').val(saleData.recorded_by);
        }, 500);
    });

    // Handle Delete Sale Button Click
    $('.delete-sale').on('click', function() {
        var saleId = $(this).data('sale-id');
        if (confirm('Are you sure you want to delete this sale record?')) {
            var form = $('<form method="post"></form>');
            form.append('<input type="hidden" name="delete_sale" value="1">');
            form.append('<input type="hidden" name="sale_id" value="' + saleId + '">');
            $('body').append(form);
            form.submit();
        }
    });

    // Initialize edit form calculations
    $('#editSaleForm input[name="quantity"], #editSaleForm input[name="rate"]').on('input', function() {
        var quantity = $('#editSaleForm input[name="quantity"]').val() || 0;
        var rate = $('#editSaleForm input[name="rate"]').val() || 0;
        var total = (quantity * rate).toFixed(2);
        $('#editSaleForm input[name="total_amount"]').val(total);
    });

    // Search functionality for non-fuel sales
    const searchInput = document.getElementById('saleSearch');
    const saleRows = document.querySelectorAll('.sale-row');
    const nonFuelSalesTableBody = document.getElementById('nonFuelSalesTableBody');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let hasResults = false;

            saleRows.forEach(row => {
                const productName = row.querySelector('.product-name').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show no results message if needed
            const existingNoResults = nonFuelSalesTableBody.querySelector('.no-results');
            if (!hasResults && !existingNoResults) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = '<td colspan="10">No sales found matching your search.</td>';
                nonFuelSalesTableBody.appendChild(noResultsRow);
            } else if (hasResults && existingNoResults) {
                existingNoResults.remove();
            }
        });
    }

    document.getElementById('dateFilter').addEventListener('change', function(e) {
        window.location.href = window.location.pathname + '?date=' + this.value;
    });

    if (dateFilter && !dateFilter.value) {
        dateFilter.value = new Date().toISOString().split('T')[0];
    }

    if (readingDate && !readingDate.value) {
        readingDate.value = new Date().toISOString().split('T')[0];
    }
});
</script>

<?php include 'includes/footer.php'; ?> 