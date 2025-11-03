<?php
session_start();
require_once 'config.php';

// Check if user is authorized
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to validate date is not in future
function isFutureDate($date) {
    return strtotime($date) > strtotime(date('Y-m-d'));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_reading':
                $pump_id = $_POST['pump_id'];
                $reading_date = $_POST['reading_date'];
                $opening_reading = $_POST['opening_reading'];
                $closing_reading = $_POST['closing_reading'];

                // Check for future date
                if (isFutureDate($reading_date)) {
                    $_SESSION['error'] = "Cannot add readings for future dates!";
                    header("Location: daily_reading.php");
                    exit();
                }

                // Check if reading already exists for this pump and date
                $check_query = "SELECT reading_id FROM daily_readings 
                              WHERE pump_id = ? AND reading_date = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("is", $pump_id, $reading_date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $_SESSION['error'] = "A reading for this pump and date already exists!";
                    header("Location: daily_reading.php");
                    exit();
                }

                $stmt = $conn->prepare("INSERT INTO daily_readings (pump_id, reading_date, opening_reading, closing_reading) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isdd", $pump_id, $reading_date, $opening_reading, $closing_reading);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Daily reading recorded successfully!";
                    header("Location: daily_reading.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Error recording daily reading: " . $conn->error;
                    header("Location: daily_reading.php");
                    exit();
                }
                break;
            
            case 'edit_reading':
                $reading_id = $_POST['reading_id'];
                $opening_reading = $_POST['opening_reading'];
                $closing_reading = $_POST['closing_reading'];

                // Get the reading date for validation
                $date_query = "SELECT reading_date FROM daily_readings WHERE reading_id = ?";
                $date_stmt = $conn->prepare($date_query);
                $date_stmt->bind_param("i", $reading_id);
                $date_stmt->execute();
                $date_result = $date_stmt->get_result();
                $reading_data = $date_result->fetch_assoc();

                // Check for future date
                if (isFutureDate($reading_data['reading_date'])) {
                    $_SESSION['error'] = "Cannot edit readings for future dates!";
                    header("Location: daily_reading.php");
                    exit();
                }

                $stmt = $conn->prepare("UPDATE daily_readings 
                                      SET opening_reading = ?, closing_reading = ? 
                                      WHERE reading_id = ?");
                $stmt->bind_param("ddi", $opening_reading, $closing_reading, $reading_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Reading updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating reading: " . $conn->error;
                }
                header("Location: daily_reading.php");
                exit();
                break;

            case 'delete_reading':
                $reading_id = $_POST['reading_id'];
                
                $stmt = $conn->prepare("DELETE FROM daily_readings WHERE reading_id = ?");
                $stmt->bind_param("i", $reading_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Reading deleted successfully!";
                } else {
                    $_SESSION['error'] = "Error deleting reading: " . $conn->error;
                }
                header("Location: daily_reading.php");
                exit();
                break;
        }
    }
}

// Get all pumps
$pumps_query = "SELECT p.pump_id, p.pump_name, pr.product_name 
                FROM fuel_pumps p 
                JOIN products pr ON p.product_id = pr.product_id 
                WHERE p.status = 'active'";
$pumps_result = $conn->query($pumps_query);

// Get filter parameters
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$filter_pump = isset($_GET['filter_pump']) ? $_GET['filter_pump'] : '';

// Modify the readings query to include filtering
$readings_query = "SELECT dr.*, p.pump_name, pr.product_name, pr.selling_price 
                  FROM daily_readings dr 
                  JOIN fuel_pumps p ON dr.pump_id = p.pump_id 
                  JOIN products pr ON p.product_id = pr.product_id 
                  WHERE 1=1";

if (!empty($filter_date)) {
    $readings_query .= " AND DATE(dr.reading_date) = ?";
}
if (!empty($filter_pump)) {
    $readings_query .= " AND dr.pump_id = ?";
}

$readings_query .= " ORDER BY dr.reading_date DESC, dr.pump_id";

// Prepare and execute the query with filters
$stmt = $conn->prepare($readings_query);
if (!empty($filter_date) && !empty($filter_pump)) {
    $stmt->bind_param("si", $filter_date, $filter_pump);
} elseif (!empty($filter_date)) {
    $stmt->bind_param("s", $filter_date);
} elseif (!empty($filter_pump)) {
    $stmt->bind_param("i", $filter_pump);
}
$stmt->execute();
$readings_result = $stmt->get_result();

// Get summary data
$summary_query = "SELECT 
                    DATE(dr.reading_date) as reading_date,
                    p.pump_name,
                    pr.product_name,
                    COUNT(*) as reading_count,
                    SUM(dr.closing_reading - dr.opening_reading) as total_sales_liters,
                    SUM((dr.closing_reading - dr.opening_reading) * pr.selling_price) as total_sales_amount
                  FROM daily_readings dr 
                  JOIN fuel_pumps p ON dr.pump_id = p.pump_id 
                  JOIN products pr ON p.product_id = pr.product_id 
                  WHERE 1=1";

if (!empty($filter_date)) {
    $summary_query .= " AND DATE(dr.reading_date) = ?";
}
if (!empty($filter_pump)) {
    $summary_query .= " AND dr.pump_id = ?";
}

$summary_query .= " GROUP BY DATE(dr.reading_date), p.pump_name, pr.product_name
                   ORDER BY DATE(dr.reading_date) DESC, p.pump_name";

$summary_stmt = $conn->prepare($summary_query);
if (!empty($filter_date) && !empty($filter_pump)) {
    $summary_stmt->bind_param("si", $filter_date, $filter_pump);
} elseif (!empty($filter_date)) {
    $summary_stmt->bind_param("s", $filter_date);
} elseif (!empty($filter_pump)) {
    $summary_stmt->bind_param("i", $filter_pump);
}
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fuel Pumps Readings</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addReadingModal">
                        <i class="fas fa-plus"></i> Add New Reading
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <?php
                $total_sales_liters = 0;
                $total_sales_amount = 0;
                $total_readings = 0;
                
                while ($summary = $summary_result->fetch_assoc()) {
                    $total_sales_liters += $summary['total_sales_liters'];
                    $total_sales_amount += $summary['total_sales_amount'];
                    $total_readings += $summary['reading_count'];
                }
                ?>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales (Liters)</h5>
                            <h2 class="card-text"><?php echo number_format($total_sales_liters, 2); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales (Amount)</h5>
                            <h2 class="card-text">Rs. <?php echo number_format($total_sales_amount, 2); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Readings</h5>
                            <h2 class="card-text"><?php echo $total_readings; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Average Sales per Reading</h5>
                            <h2 class="card-text"><?php echo $total_readings > 0 ? number_format($total_sales_liters / $total_readings, 2) : 0; ?> L</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="filterModalLabel">Filter Readings</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="GET" action="">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="filter_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="filter_date" name="filter_date" 
                                           value="<?php echo $filter_date; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="filter_pump" class="form-label">Pump</label>
                                    <select class="form-select" id="filter_pump" name="filter_pump">
                                        <option value="">All Pumps</option>
                                        <?php 
                                        $pumps_result->data_seek(0);
                                        while ($pump = $pumps_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $pump['pump_id']; ?>" 
                                                    <?php echo $filter_pump == $pump['pump_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pump['pump_name'] . ' - ' . $pump['product_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="daily_reading.php" class="btn btn-secondary">Clear Filters</a>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Readings Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 10%">Date</th>
                            <th class="text-center" style="width: 15%">Pump Details</th>
                            <th class="text-center" style="width: 15%">Product</th>
                            <th class="text-center" style="width: 12%">Opening Reading (Liters)</th>
                            <th class="text-center" style="width: 12%">Closing Reading (Liters)</th>
                            <th class="text-center" style="width: 12%">Sales (Liters)</th>
                            <th class="text-center" style="width: 12%">Amount (Rs.)</th>
                            <th class="text-center" style="width: 12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_date = null;
                        $readings_result->data_seek(0);
                        while ($reading = $readings_result->fetch_assoc()): 
                            $reading_date = date('d M Y', strtotime($reading['reading_date']));
                            $sales = $reading['closing_reading'] - $reading['opening_reading'];
                            $amount = $sales * $reading['selling_price'];
                            
                            if ($current_date !== $reading_date):
                                if ($current_date !== null): ?>
                                    <tr class="table-light">
                                        <td colspan="8" class="text-center fw-bold">
                                            <?php echo $reading_date; ?>
                                        </td>
                                    </tr>
                                <?php endif;
                                $current_date = $reading_date;
                            endif;
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $reading_date; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo htmlspecialchars($reading['pump_name']); ?></span>
                                        <small class="text-muted">Pump ID: <?php echo $reading['pump_id']; ?></small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">
                                        <?php echo htmlspecialchars($reading['product_name']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format($reading['opening_reading'], 2); ?>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format($reading['closing_reading'], 2); ?>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold <?php echo $sales > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($sales, 2); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold <?php echo $amount > 0 ? 'text-success' : 'text-danger'; ?>">
                                        Rs. <?php echo number_format($amount, 2); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-primary edit-reading" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editReadingModal"
                                                data-reading-id="<?php echo $reading['reading_id']; ?>"
                                                data-opening="<?php echo $reading['opening_reading']; ?>"
                                                data-closing="<?php echo $reading['closing_reading']; ?>"
                                                title="Edit Reading">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-reading"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteReadingModal"
                                                data-reading-id="<?php echo $reading['reading_id']; ?>"
                                                title="Delete Reading">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td colspan="2" class="text-end fw-bold">
                                <?php
                                $readings_result->data_seek(0);
                                $total_sales = 0;
                                $total_amount = 0;
                                while ($row = $readings_result->fetch_assoc()) {
                                    $sales = $row['closing_reading'] - $row['opening_reading'];
                                    $total_sales += $sales;
                                    $total_amount += ($sales * $row['selling_price']);
                                }
                                ?>
                            </td>
                            <td class="text-end fw-bold">
                                <?php echo number_format($total_sales, 2); ?> L
                            </td>
                            <td class="text-end fw-bold">
                                Rs. <?php echo number_format($total_amount, 2); ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <style>
                .table > :not(caption) > * > * {
                    padding: 0.75rem;
                }
                .table tbody tr:hover {
                    background-color: rgba(0,0,0,.075);
                }
                .badge {
                    font-size: 0.9em;
                    padding: 0.5em 0.8em;
                }
                .btn-group .btn {
                    padding: 0.25rem 0.5rem;
                }
                .table-dark th {
                    background-color: #212529;
                    color: white;
                }
                .text-success {
                    color: #198754 !important;
                }
                .text-danger {
                    color: #dc3545 !important;
                }
                .card {
                    transition: transform 0.2s;
                }
                .card:hover {
                    transform: translateY(-5px);
                }
                .card-title {
                    font-size: 0.9rem;
                    margin-bottom: 0.5rem;
                }
                .card-text {
                    font-size: 1.5rem;
                    margin-bottom: 0;
                }
            </style>
        </main>
    </div>
</div>

<!-- Add Reading Modal -->
<div class="modal fade" id="addReadingModal" tabindex="-1" aria-labelledby="addReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReadingModalLabel">Add New Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="addReadingForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_reading">
                    
                    <div class="mb-3">
                        <label for="pump_id" class="form-label">Select Pump</label>
                        <select class="form-select" id="pump_id" name="pump_id" required>
                            <option value="">Choose pump...</option>
                            <?php while ($pump = $pumps_result->fetch_assoc()): ?>
                                <option value="<?php echo $pump['pump_id']; ?>">
                                    <?php echo htmlspecialchars($pump['pump_name'] . ' - ' . $pump['product_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reading_date" class="form-label">Reading Date</label>
                        <input type="date" class="form-control" id="reading_date" name="reading_date" 
                               max="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="opening_reading" class="form-label">Opening Reading</label>
                        <input type="number" step="0.01" class="form-control" id="opening_reading" 
                               name="opening_reading" required>
                    </div>

                    <div class="mb-3">
                        <label for="closing_reading" class="form-label">Closing Reading</label>
                        <input type="number" step="0.01" class="form-control" id="closing_reading" 
                               name="closing_reading" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Reading</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Reading Modal -->
<div class="modal fade" id="editReadingModal" tabindex="-1" aria-labelledby="editReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReadingModalLabel">Edit Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_reading">
                    <input type="hidden" name="reading_id" id="edit_reading_id">
                    
                    <div class="mb-3">
                        <label for="edit_opening_reading" class="form-label">Opening Reading</label>
                        <input type="number" step="0.01" class="form-control" id="edit_opening_reading" 
                               name="opening_reading" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_closing_reading" class="form-label">Closing Reading</label>
                        <input type="number" step="0.01" class="form-control" id="edit_closing_reading" 
                               name="closing_reading" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Reading</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Reading Modal -->
<div class="modal fade" id="deleteReadingModal" tabindex="-1" aria-labelledby="deleteReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteReadingModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this reading? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_reading">
                    <input type="hidden" name="reading_id" id="delete_reading_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Existing validation code
        $('form').on('submit', function(e) {
            const opening = parseFloat($('#opening_reading').val() || $('#edit_opening_reading').val());
            const closing = parseFloat($('#closing_reading').val() || $('#edit_closing_reading').val());
            const readingDate = $('#reading_date').val();
            
            // Validate closing reading is greater than opening reading
            if (closing < opening) {
                e.preventDefault();
                alert('Closing reading cannot be less than opening reading!');
                return;
            }

            // Validate date is not in future
            if (new Date(readingDate) > new Date()) {
                e.preventDefault();
                alert('Cannot add readings for future dates!');
                return;
            }
        });

        // Edit reading modal
        $('.edit-reading').click(function() {
            const readingId = $(this).data('reading-id');
            const opening = $(this).data('opening');
            const closing = $(this).data('closing');
            
            $('#edit_reading_id').val(readingId);
            $('#edit_opening_reading').val(opening);
            $('#edit_closing_reading').val(closing);
        });

        // Delete reading modal
        $('.delete-reading').click(function() {
            const readingId = $(this).data('reading-id');
            $('#delete_reading_id').val(readingId);
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 