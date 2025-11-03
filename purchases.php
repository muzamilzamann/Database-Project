<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Set page title and current page for header
$page_title = "Purchases";
$current_page = "purchases";

// Include header
require_once 'includes/header.php';

// Get today's date
$today = date('Y-m-d');

// Get summary statistics
try {
    // Get today's total purchases
    $today_stats_query = "SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(quantity), 0) as total_volume,
        COALESCE(SUM(total_amount), 0) as total_amount
    FROM purchases 
    WHERE DATE(purchase_date) = ?";
    
    $stats_stmt = $conn->prepare($today_stats_query);
    $stats_stmt->bind_param("s", $today);
    $stats_stmt->execute();
    $today_stats = $stats_stmt->get_result()->fetch_assoc();
                
    // Get pending deliveries
    $pending_query = "SELECT COUNT(*) as pending_count 
                     FROM purchases 
                     WHERE payment_status = 'pending'";
    $pending_result = $conn->query($pending_query);
    $pending_count = $pending_result->fetch_assoc()['pending_count'];

            } catch (Exception $e) {
    error_log("Error getting purchase stats: " . $e->getMessage());
    $today_stats = [
        'total_transactions' => 0,
        'total_volume' => 0,
        'total_amount' => 0
    ];
    $pending_count = 0;
}
?>

<div class="container-fluid px-4">
    
        

    <!-- Main Content -->
    <div class="row mt-4">
                <div class="col-12">
            <div class="dashboard-card">
                <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-shopping-bag me-2"></i>Purchase Records</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                        <i class="fas fa-plus me-2"></i>Add New Purchase
                    </button>
                </div>
                <div class="dashboard-card-body">
                    <!-- Tabs -->
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-all-tab" data-bs-toggle="tab" data-bs-target="#nav-all" type="button" role="tab">
                                <i class="fas fa-list me-2"></i>All Purchases
                            </button>
        </div>
                    </nav>

                    <div class="tab-content mt-4" id="nav-tabContent">
                        <!-- All Purchases Tab -->
                        <div class="tab-pane fade show active" id="nav-all" role="tabpanel">
            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Quantity</th>
                                            <th>Rate</th>
                                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, pr.product_name, s.name as supplier_name 
                                               FROM purchases p 
                                               JOIN products pr ON p.product_id = pr.product_id 
                                               JOIN suppliers s ON p.supplier_id = s.supplier_id 
                                               ORDER BY p.purchase_date DESC";
                                        $result = $conn->query($sql);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . date('d M Y', strtotime($row['purchase_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['supplier_name']) . "</td>";
                                            echo "<td>" . number_format($row['quantity'], 2) . " L</td>";
                                            echo "<td>Rs. " . number_format($row['price_per_unit'], 2) . "</td>";
                                            echo "<td>Rs. " . number_format($row['total_amount'], 2) . "</td>";
                                            echo "<td>
                                                    <button class='btn btn-sm btn-primary edit-purchase' data-id='" . $row['purchase_id'] . "'>
                                                        <i class='fas fa-edit'></i>
                                        </button>
                                                    <button class='btn btn-sm btn-danger delete-purchase' data-id='" . $row['purchase_id'] . "'>
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
    </div>
</div>

<style>
/* Inherit existing dashboard styles */
.container-fluid {
    padding: 2rem;
    max-width: 1800px;
    margin: 0 auto;
}

.stats-row {
    margin-bottom: 2rem;
}

/* Status Badge Styles */
.status-badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-weight: 500;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.completed {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.cancelled {
    background-color: #f8d7da;
    color: #721c24;
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

/* Responsive Design */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem;
}

    .nav-tabs .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
}

    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete purchase
    document.querySelectorAll('.delete-purchase').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this purchase record?')) {
                const purchaseId = this.dataset.id;
                // Add delete functionality here
            }
        });
    });

    // Handle edit purchase
    document.querySelectorAll('.edit-purchase').forEach(button => {
        button.addEventListener('click', function() {
            const purchaseId = this.dataset.id;
            // Add edit functionality here
        });
    });

    // Initialize select2 for dropdowns if using Select2
    if(typeof $.fn.select2 !== 'undefined') {
        $('#product_id, #supplier_id').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Calculate total amount when quantity or rate changes
    $('#quantity, #price_per_unit').on('input', function() {
        const quantity = parseFloat($('#quantity').val()) || 0;
        const rate = parseFloat($('#price_per_unit').val()) || 0;
        const total = quantity * rate;
        $('#total_amount').val(total.toFixed(2));
    });
});
</script>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPurchaseModalLabel">Add New Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPurchaseForm" action="includes/process_purchase.php" method="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php
                                $supplier_query = "SELECT supplier_id, name FROM suppliers ORDER BY name";
                                $supplier_result = $conn->query($supplier_query);
                                while ($supplier = $supplier_result->fetch_assoc()) {
                                    echo "<option value='" . $supplier['supplier_id'] . "'>" . htmlspecialchars($supplier['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php
                                $product_query = "SELECT product_id, product_name FROM products ORDER BY product_name";
                                $product_result = $conn->query($product_query);
                                while ($product = $product_result->fetch_assoc()) {
                                    echo "<option value='" . $product['product_id'] . "'>" . htmlspecialchars($product['product_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="stock_quantity" class="form-label">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price_per_unit" class="form-label">Rate (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" id="price_per_unit" name="price_per_unit" required>
                        </div>
                        <div class="col-md-6">
                            <label for="total_amount" class="form-label">Total Amount (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 