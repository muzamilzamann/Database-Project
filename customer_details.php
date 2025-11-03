<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Customer Details';
$current_page = 'customers';

// Get customer ID from URL
$customer_id = $_GET['id'] ?? 0;

// Get customer details
$customer_query = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = prepare_query($customer_query, [$customer_id]);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    header('Location: customers.php');
    exit();
}

// Get customer statistics
$stats_query = "SELECT 
    COUNT(*) as total_transactions,
    MAX(sale_date) as last_transaction_date,
    COALESCE(SUM(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE) THEN total_amount ELSE 0 END), 0) as current_month_total
FROM sales 
WHERE customer_id = ?";
$stats_stmt = prepare_query($stats_query, [$customer_id]);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get monthly spending history (last 12 months)
$monthly_history_query = "SELECT 
    DATE_FORMAT(sale_date, '%Y-%m') as month,
    SUM(CASE WHEN p.product_type = 'fuel' THEN s.total_amount ELSE 0 END) as fuel_amount,
    SUM(CASE WHEN p.product_type = 'non-fuel' THEN s.total_amount ELSE 0 END) as product_amount,
    SUM(s.total_amount) as total_amount
FROM sales s
JOIN products p ON s.product_id = p.product_id
WHERE s.customer_id = ?
    AND s.sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
ORDER BY month DESC";
$monthly_stmt = prepare_query($monthly_history_query, [$customer_id]);
$monthly_stmt->execute();
$monthly_history = $monthly_stmt->get_result();

// Get fuel sales history
$fuel_sales_query = "SELECT s.*, p.product_name, fp.pump_name 
                     FROM sales s 
                     JOIN products p ON s.product_id = p.product_id
                     LEFT JOIN fuel_pumps fp ON s.pump_id = fp.pump_id
                     WHERE s.customer_id = ? AND p.product_type = 'fuel'
                     ORDER BY s.sale_date DESC";
$fuel_stmt = prepare_query($fuel_sales_query, [$customer_id]);
$fuel_stmt->execute();
$fuel_sales = $fuel_stmt->get_result();

// Get product sales history
$product_sales_query = "SELECT s.*, p.product_name 
                       FROM sales s 
                       JOIN products p ON s.product_id = p.product_id
                       WHERE s.customer_id = ? AND p.product_type = 'non-fuel'
                       ORDER BY s.sale_date DESC";
$product_stmt = prepare_query($product_sales_query, [$customer_id]);
$product_stmt->execute();
$product_sales = $product_stmt->get_result();

// Calculate total spending
$total_query = "SELECT 
    SUM(CASE WHEN p.product_type = 'fuel' THEN s.total_amount ELSE 0 END) as fuel_total,
    SUM(CASE WHEN p.product_type = 'non-fuel' THEN s.total_amount ELSE 0 END) as product_total
FROM sales s
JOIN products p ON s.product_id = p.product_id
WHERE s.customer_id = ?";
$total_stmt = prepare_query($total_query, [$customer_id]);
$total_stmt->execute();
$totals = $total_stmt->get_result()->fetch_assoc();
$total_spending = $totals['fuel_total'] + $totals['product_total'];

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Customer Information Card -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <h4 class="card-title"><?php echo htmlspecialchars($customer['name']); ?></h4>
                    <p class="card-text">
                        <strong>Type:</strong> 
                        <span class="badge bg-<?php echo $customer['customer_type'] === 'commercial' ? 'primary' : 'secondary'; ?>">
                            <?php echo ucfirst($customer['customer_type']); ?>
                        </span>
                    </p>
                    <p class="card-text">
                        <strong>Email:</strong><br>
                        <?php if ($customer['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </p>
                    <p class="card-text">
                        <strong>Phone:</strong><br>
                        <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>">
                            <?php echo htmlspecialchars($customer['phone']); ?>
                        </a>
                    </p>
                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <h6 class="card-title">Total Spending</h6>
                            <h3 class="text-primary mb-0">Rs. <?php echo number_format($total_spending, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Statistics -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Customer Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Total Transactions</small>
                                <strong class="fs-5"><?php echo $stats['total_transactions']; ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Last Transaction</small>
                                <strong class="fs-5">
                                    <?php echo $stats['last_transaction_date'] ? date('d M Y', strtotime($stats['last_transaction_date'])) : 'N/A'; ?>
                                </strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Current Month Spending</small>
                                <strong class="fs-5">Rs. <?php echo number_format($stats['current_month_total'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Monthly Spending History -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Monthly Spending History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Fuel Spending</th>
                                    <th>Product Spending</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($month = $monthly_history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td>Rs. <?php echo number_format($month['fuel_amount'], 2); ?></td>
                                        <td>Rs. <?php echo number_format($month['product_amount'], 2); ?></td>
                                        <td>
                                            <strong>Rs. <?php echo number_format($month['total_amount'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($monthly_history->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No spending history found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transaction History Tabs -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" id="purchaseTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="fuel-tab" data-bs-toggle="tab" href="#fuel" role="tab">
                                <i class="fas fa-gas-pump me-2"></i>Fuel Purchases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="product-tab" data-bs-toggle="tab" href="#products" role="tab">
                                <i class="fas fa-shopping-bag me-2"></i>Product Purchases
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="purchaseTabContent">
                        <!-- Fuel Sales Tab -->
                        <div class="tab-pane fade show active" id="fuel" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Pump</th>
                                            <th>Fuel</th>
                                            <th>Quantity</th>
                                            <th>Rate</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($sale = $fuel_sales->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d M Y H:i', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sale['pump_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                                <td><?php echo number_format($sale['quantity'], 2); ?> L</td>
                                                <td>Rs. <?php echo number_format($sale['rate'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($sale['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $sale['payment_method'] === 'cash' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($sale['payment_method']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($sale['recorded_by'] ?? 'System'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if ($fuel_sales->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No fuel purchases found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Product Sales Tab -->
                        <div class="tab-pane fade" id="products" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Rate</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($sale = $product_sales->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d M Y H:i', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                                <td><?php echo number_format($sale['quantity']); ?></td>
                                                <td>Rs. <?php echo number_format($sale['rate'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($sale['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $sale['payment_method'] === 'cash' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($sale['payment_method']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($sale['recorded_by'] ?? 'System'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if ($product_sales->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No product purchases found</td>
                                            </tr>
                                        <?php endif; ?>
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
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.badge {
    padding: 0.5em 0.75em;
}

.border.rounded {
    transition: all 0.3s ease;
}

.border.rounded:hover {
    background-color: #f8f9fa;
}

.fs-5 {
    font-size: 1.1rem !important;
}
</style>

<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 