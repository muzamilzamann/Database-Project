<?php
$page_title = 'Reports';
$current_page = 'reports';
require_once 'includes/header.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Get date range from request, default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['type'] ?? 'sales';



// Get report data based on type
$report_data = [];
$summary_data = [];

// Get overall summary data regardless of report type
$overall_summary = [];

// Get total sales for current month
$sales_query = "SELECT 
    COUNT(*) as total_sales_count,
    COALESCE(SUM(total_amount), 0) as total_sales_amount
FROM sales 
WHERE MONTH(sale_date) = MONTH(CURRENT_DATE())
AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
$sales_result = $conn->query($sales_query);
$overall_summary['sales'] = $sales_result->fetch_assoc();

// Get total expenses for current month
$expenses_query = "SELECT 
    COUNT(*) as total_expenses_count,
    COALESCE(SUM(amount), 0) as total_expenses_amount
FROM expenses 
WHERE MONTH(expense_date) = MONTH(CURRENT_DATE())
AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$expenses_result = $conn->query($expenses_query);
$overall_summary['expenses'] = $expenses_result->fetch_assoc();

// Get total purchases for current month
$purchases_query = "SELECT 
    COUNT(*) as total_purchases_count,
    COALESCE(SUM(total_amount), 0) as total_purchases_amount
FROM purchases 
WHERE MONTH(purchase_date) = MONTH(CURRENT_DATE())
AND YEAR(purchase_date) = YEAR(CURRENT_DATE())";
$purchases_result = $conn->query($purchases_query);
$overall_summary['purchases'] = $purchases_result->fetch_assoc();

switch ($report_type) {
    case 'sales':
        // Get sales data
        $query = "SELECT 
                    DATE(sale_date) as date,
                    COUNT(*) as transactions,
                    SUM(total_amount) as total_amount,
                    CASE 
                        WHEN p.product_type = 'fuel' THEN 'Fuel'
                        ELSE 'Product'
                    END as type
                  FROM sales s
                  JOIN products p ON s.product_id = p.product_id
                  WHERE DATE(sale_date) BETWEEN ? AND ?
                  GROUP BY DATE(sale_date), p.product_type
                  ORDER BY date DESC, type";
        $stmt = prepare_query($query, [$start_date, $end_date]);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate summary
        $summary_query = "SELECT 
                           SUM(total_amount) as total_sales,
                           COUNT(*) as total_transactions
                         FROM sales 
                         WHERE DATE(sale_date) BETWEEN ? AND ?";
        $stmt = prepare_query($summary_query, [$start_date, $end_date]);
        $stmt->execute();
        $summary_data = $stmt->get_result()->fetch_assoc();
        break;
        
    case 'expenses':
        // Get expenses data
        $query = "SELECT 
                    DATE(e.expense_date) as date,
                    e.expense_type,
                    e.amount,
                    e.description,
                    COALESCE(a.name, 'Unknown') as recorded_by_name
                  FROM expenses e
                  LEFT JOIN attendants a ON e.recorded_by = a.attendant_id
                  WHERE DATE(e.expense_date) BETWEEN ? AND ?
                  ORDER BY e.expense_date DESC";
        $stmt = prepare_query($query, [$start_date, $end_date]);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate summary
        $summary_query = "SELECT 
                           SUM(amount) as total_expenses,
                           COUNT(*) as total_transactions
                         FROM expenses
                         WHERE DATE(expense_date) BETWEEN ? AND ?";
        $stmt = prepare_query($summary_query, [$start_date, $end_date]);
        $stmt->execute();
        $summary_data = $stmt->get_result()->fetch_assoc();
        break;
}
?>
<div class="container mt-4 page-transition">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Monthly Sales</h6>
                            <h3 class="mt-2 mb-0">Rs. <?php echo number_format($overall_summary['sales']['total_sales_amount'], 2); ?></h3>
                            <small><?php echo number_format($overall_summary['sales']['total_sales_count']); ?> transactions</small>
                        </div>
                        <div class="fs-1">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Monthly Purchases</h6>
                            <h3 class="mt-2 mb-0">Rs. <?php echo number_format($overall_summary['purchases']['total_purchases_amount'], 2); ?></h3>
                            <small><?php echo number_format($overall_summary['purchases']['total_purchases_count']); ?> transactions</small>
                        </div>
                        <div class="fs-1">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Monthly Expenses</h6>
                            <h3 class="mt-2 mb-0">Rs. <?php echo number_format($overall_summary['expenses']['total_expenses_amount'], 2); ?></h3>
                            <small><?php echo number_format($overall_summary['expenses']['total_expenses_count']); ?> transactions</small>
                        </div>
                        <div class="fs-1">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Reports</h4>
            <div>
                <?php if (!empty($report_data)): ?>
                    <button onclick="window.print();" class="btn btn-light btn-sm">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Report Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                            <option value="expenses" <?php echo $report_type === 'expenses' ? 'selected' : ''; ?>>Expenses Report</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Generate Report
                        </button>
                    </div>
                </div>
            </form>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <?php
                                switch ($report_type) {
                                    case 'sales':
                                        echo '<th>Date</th><th>Type</th><th>Transactions</th><th>Total Amount</th>';
                                        break;
                                    case 'expenses':
                                        echo '<th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Recorded By</th>';
                                        break;
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <?php
                                    switch ($report_type) {
                                        case 'sales':
                                            echo '<td>' . htmlspecialchars($row['date']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['type']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['transactions']) . '</td>';
                                            echo '<td>Rs. ' . number_format($row['total_amount'], 2) . '</td>';
                                            break;
                                        case 'expenses':
                                            echo '<td>' . htmlspecialchars($row['date'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($row['expense_type'] ?? '') . '</td>';
                                            echo '<td>Rs. ' . number_format($row['amount'] ?? 0, 2) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($row['recorded_by_name'] ?? 'Unknown') . '</td>';
                                            break;
                                    }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (!empty($summary_data)): ?>
                            <tfoot class="table-dark">
                                <tr>
                                    <?php
                                    switch ($report_type) {
                                        case 'sales':
                                            echo '<td colspan="2"><strong>Total</strong></td>';
                                            echo '<td><strong>' . number_format($summary_data['total_transactions']) . '</strong></td>';
                                            echo '<td><strong>Rs. ' . number_format($summary_data['total_sales'], 2) . '</strong></td>';
                                            break;
                                        case 'expenses':
                                            echo '<td colspan="2"><strong>Total Expenses</strong></td>';
                                            echo '<td colspan="3"><strong>Rs. ' . number_format($summary_data['total_expenses'], 2) . '</strong></td>';
                                            break;
                                    }
                                    ?>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No data available for the selected criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Bootstrap and other JS dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* Print styles */
@media print {
    /* Hide non-essential elements when printing */
    .btn, 
    form,
    .no-print {
        display: none !important;
    }
    
    /* Reset background colors and shadows for better printing */
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background-color: transparent !important;
        color: #000 !important;
        padding: 0 !important;
        margin-bottom: 20px !important;
    }
    
    .table {
        width: 100% !important;
        margin-bottom: 1rem !important;
        color: #000 !important;
        border-collapse: collapse !important;
    }
    
    .table th,
    .table td {
        background-color: #fff !important;
        border: 1px solid #dee2e6 !important;
        padding: 8px !important;
    }
    
    .table-dark {
        background-color: #fff !important;
        color: #000 !important;
    }
    
    /* Add page break settings */
    table { page-break-inside: auto !important; }
    tr { page-break-inside: avoid !important; page-break-after: auto !important; }
    thead { display: table-header-group !important; }
    tfoot { display: table-footer-group !important; }
    
    /* Add report title and date range */
    .card-header::after {
        content: " (" attr(data-date-range) ")" !important;
        font-size: 14px !important;
        font-weight: normal !important;
    }
    
    /* Ensure white background */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Remove animations */
    * {
        animation: none !important;
        transition: none !important;
    }
}
</style>

<script>
$(document).ready(function() {
    // Show loading bar when form is submitted
    $('form').on('submit', function() {
        $('#loadingBar').css('width', '90%');
    });

    // Hide loading bar when page is fully loaded
    $(window).on('load', function() {
        $('#loadingBar').css('width', '100%');
        setTimeout(function() {
            $('#loadingBar').css('width', '0%');
        }, 400);
    });
    
    // Add date range to header for printing
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    $('.card-header').attr('data-date-range', startDate + ' to ' + endDate);
});
</script>
</body>
</html> 
<?php require_once 'includes/footer.php'; ?> 