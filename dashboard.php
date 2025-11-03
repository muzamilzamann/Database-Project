<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Set page title and current page for header
$page_title = 'Dashboard';
$current_page = 'dashboard';

// Include header
require_once 'includes/header.php';

// Get today's total sales
$today = date('Y-m-d');
$sql = "SELECT COALESCE(SUM((dr.closing_reading - dr.opening_reading) * p.selling_price), 0) as total 
        FROM daily_readings dr
        JOIN fuel_pumps fp ON dr.pump_id = fp.pump_id
        JOIN products p ON fp.product_id = p.product_id 
        WHERE dr.reading_date = ?";
$stmt = prepare_query($sql, [$today]);
$stmt->execute();
$result = $stmt->get_result();
$todaySales = $result->fetch_assoc()['total'] ?? 0;

// Get fuel stock levels
$sql = "SELECT SUM(stock_quantity) as total FROM products WHERE product_type = 'fuel'";
$stmt = prepare_query($sql);
$stmt->execute();
$result = $stmt->get_result();
$totalStock = $result->fetch_assoc()['total'] ?? 0;

// Get active employees count
$sql = "SELECT COUNT(*) as total FROM attendants WHERE status = 'active'";
$stmt = prepare_query($sql);
$stmt->execute();
$result = $stmt->get_result();
$activeEmployees = $result->fetch_assoc()['total'] ?? 0;

// Get active pumps count
$sql = "SELECT COUNT(*) as total FROM fuel_pumps WHERE status = 'active'";
$stmt = prepare_query($sql);
$stmt->execute();
$result = $stmt->get_result();
$activePumps = $result->fetch_assoc()['total'] ?? 0;
?>

<!-- Stats Row -->
<div class="container-fluid px-4">
    <div class="row stats-row g-4">
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card stock">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-gas-pump"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5>Total Fuel Stock</h5>
                        <h3><?php echo number_format($totalStock, 2); ?> L</h3>
                        <div class="stat-change">
                            <i class="fas fa-check"></i> Stock Level Good
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card employees">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5>Active Employees</h5>
                        <h3><?php echo $activeEmployees; ?></h3>
                        <div class="stat-change">
                            <i class="fas fa-user-check"></i> On Duty
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card pumps">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5>Active Pumps</h5>
                        <h3><?php echo $activePumps; ?></h3>
                        <div class="stat-change">
                            <i class="fas fa-check-circle"></i> Operational
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Tables -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h4><i class="fas fa-history"></i> Recent Activities</h4>
                </div>
                <div class="dashboard-card-body">
                    <!-- Tabs -->
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-fuel-tab" data-bs-toggle="tab" data-bs-target="#nav-fuel" type="button" role="tab">
                                <i class="fas fa-gas-pump me-2"></i>Fuel Sales
                            </button>
                            <button class="nav-link" id="nav-product-tab" data-bs-toggle="tab" data-bs-target="#nav-product" type="button" role="tab">
                                <i class="fas fa-shopping-bag me-2"></i>Product Sales
                            </button>
                        </div>
                    </nav>
                    <div class="tab-content mt-4" id="nav-tabContent">
                        <!-- Fuel Sales Tab -->
                        <div class="tab-pane fade show active" id="nav-fuel" role="tabpanel" aria-labelledby="nav-fuel-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Fuel Type</th>
                                            <th>Quantity</th>
                                            <th>Rate</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT s.*, p.product_name as fuel_name 
                                               FROM sales s 
                                               JOIN products p ON s.product_id = p.product_id 
                                               WHERE p.product_type = 'fuel'
                                               ORDER BY s.sale_date DESC LIMIT 5";
                                        $stmt = prepare_query($sql);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['fuel_name']) . "</td>";
                                            echo "<td>" . number_format($row['quantity'], 2) . " L</td>";
                                            echo "<td>Rs. " . number_format($row['rate'], 2) . "</td>";
                                            echo "<td>Rs. " . number_format($row['total_amount'], 2) . "</td>";
                                            echo "<td><span class='payment-badge " . strtolower($row['payment_method']) . "'>" . 
                                                 ucfirst($row['payment_method']) . "</span></td>";
                                            echo "<td>" . htmlspecialchars($row['recorded_by'] ?? 'System') . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Product Sales Tab -->
                        <div class="tab-pane fade" id="nav-product" role="tabpanel" aria-labelledby="nav-product-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT s.*, p.product_name 
                                               FROM sales s 
                                               JOIN products p ON s.product_id = p.product_id 
                                               WHERE p.product_type = 'non-fuel'
                                               ORDER BY s.sale_date DESC LIMIT 5";
                                        $stmt = prepare_query($sql);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                            echo "<td>" . $row['quantity'] . "</td>";
                                            echo "<td>Rs. " . number_format($row['rate'], 2) . "</td>";
                                            echo "<td>Rs. " . number_format($row['total_amount'], 2) . "</td>";
                                            echo "<td><span class='payment-badge " . strtolower($row['payment_method']) . "'>" . 
                                                 ucfirst($row['payment_method']) . "</span></td>";
                                            echo "<td>" . htmlspecialchars($row['recorded_by'] ?? 'System') . "</td>";
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
.container-fluid {
    padding: 2rem;
    max-width: 1800px;
    margin: 0 auto;
}

.stats-row {
    margin-bottom: 2rem;
}

/* Stat Card Styles */
.stat-card {
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-card-body {
    padding: 1.5rem;
    display: flex;
    align-items: center;
}

.stat-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 1rem;
    background: rgba(255, 255, 255, 0.2);
}

.stat-card-info {
    flex: 1;
}

.stat-card-info h5 {
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.stat-card-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.stat-change {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Card Colors */
.stat-card.sales {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.stat-card.stock {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.stat-card.employees {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.stat-card.pumps {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

/* Dashboard Card Styles */
.dashboard-card {
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.dashboard-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dashboard-card-header h4 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-card-header h4 i {
    color: #3498db;
}

.dashboard-card-body {
    padding: 1.5rem;
}

/* Nav Tabs Styles */
.nav-tabs {
    border: none;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.nav-tabs .nav-link {
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.nav-tabs .nav-link.active {
    background: #3498db;
    color: #ffffff;
}

/* Table Styles */
.table {
    margin: 0;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    color: #555;
    border-bottom: 2px solid #eee;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    color: #333;
    font-size: 0.9rem;
    border-bottom: 1px solid #eee;
}

.table tbody tr:hover {
    background: rgba(52, 152, 219, 0.05);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem;
    }

    .stat-card-info h3 {
        font-size: 1.5rem;
    }

    .nav-tabs {
        gap: 0.5rem;
    }

    .nav-tabs .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('#nav-tab button'))
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault()
            tabTrigger.show()
        })
    })
});
</script>

<script>
function updateDashboard() {
    fetch('update_dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error updating dashboard:', data.error);
                return;
            }

            // Update sales amount
            document.querySelector('.sales .stat-card-info h3').textContent = data.todaySales_formatted;
            
            // Update stock level
            document.querySelector('.stock .stat-card-info h3').textContent = data.totalStock_formatted;

            // Update recent sales table
            const tbody = document.querySelector('#nav-fuel table tbody');
            if (tbody && data.recentSales) {
                tbody.innerHTML = data.recentSales.map(sale => `
                    <tr>
                        <td>${sale.date}</td>
                        <td>${sale.fuel_name}</td>
                        <td>${sale.quantity} L</td>
                        <td>Rs. ${sale.rate}</td>
                        <td>Rs. ${sale.total_amount}</td>
                        <td><span class="payment-badge ${sale.payment_method.toLowerCase()}">${sale.payment_method}</span></td>
                        <td>${sale.recorded_by}</td>
                    </tr>
                `).join('');
            }
        })
        .catch(error => console.error('Failed to update dashboard:', error));
}

// Update dashboard every 30 seconds
setInterval(updateDashboard, 30000);

// Also update when the page becomes visible again
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        updateDashboard();
    }
});
</script>

<script>
if (window.opener && !window.opener.closed) {
    if (typeof window.opener.updateDashboard === 'function') {
        window.opener.updateDashboard();
    }
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 