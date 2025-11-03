<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Expense Management';
$current_page = 'expenses';

// Initialize message variables
$success = '';
$error = '';

// Define expense types
$expense_types = [
    'Electricity' => 'Electricity bills and power consumption',
    'Water' => 'Water supply and utilities',
    'Maintenance' => 'Equipment and facility maintenance',
    'Salaries' => 'Employee salaries and wages',
    'Equipment' => 'New equipment and replacements',
    'Cleaning' => 'Cleaning services and supplies',
    'Insurance' => 'Insurance premiums',
    'Taxes' => 'Local and business taxes',
    'Office' => 'Office supplies and stationery',
    'Other' => 'Miscellaneous expenses'
];

// Handle expense operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_expense') {
        $expense_type = $_POST['expense_type'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $description = $_POST['description'] ?? '';
        $expense_date = $_POST['expense_date'] ?? '';
        
        if (empty($expense_type) || empty($amount) || empty($expense_date)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "INSERT INTO expenses (expense_type, amount, description, expense_date, recorded_by) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = prepare_query($sql, [$expense_type, $amount, $description, $expense_date, $_SESSION['user_id']]);
                
                if ($stmt->execute()) {
                    $success = 'Expense added successfully';
                } else {
                    $error = 'Failed to add expense';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while adding expense';
                error_log("Expense Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'update_expense') {
        $expense_id = $_POST['expense_id'] ?? '';
        $expense_type = $_POST['expense_type'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $description = $_POST['description'] ?? '';
        $expense_date = $_POST['expense_date'] ?? '';
        
        if (empty($expense_id) || empty($expense_type) || empty($amount) || empty($expense_date)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $check_sql = "SELECT recorded_by FROM expenses WHERE expense_id = ?";
                $check_stmt = prepare_query($check_sql, [$expense_id]);
                $check_stmt->execute();
                $expense_data = $check_stmt->get_result()->fetch_assoc();

                if ($expense_data && $expense_data['recorded_by'] == $_SESSION['user_id']) {
                    $sql = "UPDATE expenses 
                            SET expense_type = ?, amount = ?, description = ?, expense_date = ? 
                            WHERE expense_id = ? AND recorded_by = ?";
                    $stmt = prepare_query($sql, [
                        $expense_type, 
                        $amount, 
                        $description, 
                        $expense_date, 
                        $expense_id,
                        $_SESSION['user_id']
                    ]);
                    
                    if ($stmt->execute()) {
                        $success = 'Expense updated successfully';
                    } else {
                        $error = 'Failed to update expense';
                    }
                } else {
                    $error = 'You can only edit expenses that you have recorded';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating expense';
                error_log("Expense Error: " . $e->getMessage());
            }
        }
    }
}

// Get expense summary for the current month
$current_month_summary = "
    SELECT expense_type, 
           COUNT(*) as count, 
           SUM(amount) as total_amount
    FROM expenses 
    WHERE MONTH(expense_date) = MONTH(CURRENT_DATE())
    AND YEAR(expense_date) = YEAR(CURRENT_DATE())
    GROUP BY expense_type";
$summary_result = $conn->query($current_month_summary);

// Get all expenses with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$expenses_query = "
    SELECT e.*, a.name as recorded_by_name
    FROM expenses e
    LEFT JOIN attendants a ON e.recorded_by = a.attendant_id
    ORDER BY e.expense_date DESC, e.expense_id DESC
    LIMIT ? OFFSET ?";
$stmt = prepare_query($expenses_query, [$limit, $offset]);
$stmt->execute();
$expenses_result = $stmt->get_result();

// Get total number of expenses for pagination
$total_query = "SELECT COUNT(*) as total FROM expenses";
$total_result = $conn->query($total_query);
$total_expenses = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_expenses / $limit);

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Current Month Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Current Month Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php 
                $total_month_expense = 0;
                while ($summary = $summary_result->fetch_assoc()):
                    $total_month_expense += $summary['total_amount'];
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="summary-card">
                            <h6><?php echo htmlspecialchars($summary['expense_type']); ?></h6>
                            <div>Count: <?php echo number_format($summary['count']); ?></div>
                            <div>Amount: Rs. <?php echo number_format($summary['total_amount'], 2); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <strong>Total Expenses This Month: Rs. <?php echo number_format($total_month_expense, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Expense -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Expense</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="add_expense">
                
                <div class="col-md-4">
                    <label for="expense_type" class="form-label">Expense Type</label>
                    <select class="form-select" id="expense_type" name="expense_type" required>
                        <option value="">Select Type</option>
                        <?php foreach ($expense_types as $type => $description): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                    title="<?php echo htmlspecialchars($description); ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="amount" class="form-label">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="expense_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="expense_date" name="expense_date" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Expense List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($expense = $expenses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary expense-type-badge">
                                        <?php echo htmlspecialchars($expense['expense_type'] ?? ''); ?>
                                    </span>
                                </td>
                                <td class="amount-text">
                                    Rs. <?php echo number_format($expense['amount'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($expense['description'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($expense['recorded_by_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php if ($expense['recorded_by'] == $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $expense['expense_id']; ?>"
                                                title="Edit Expense">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $expense['expense_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Expense</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="" class="row g-3">
                                                <input type="hidden" name="action" value="update_expense">
                                                <input type="hidden" name="expense_id" value="<?php echo $expense['expense_id']; ?>">
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_type<?php echo $expense['expense_id']; ?>" class="form-label">Expense Type</label>
                                                    <select class="form-select" id="edit_type<?php echo $expense['expense_id']; ?>" 
                                                            name="expense_type" required>
                                                        <?php foreach ($expense_types as $type => $description): ?>
                                                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                                                    <?php echo $expense['expense_type'] === $type ? 'selected' : ''; ?>
                                                                    title="<?php echo htmlspecialchars($description); ?>">
                                                                <?php echo htmlspecialchars($type); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_amount<?php echo $expense['expense_id']; ?>" class="form-label">Amount</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rs.</span>
                                                        <input type="number" step="0.01" class="form-control" 
                                                               id="edit_amount<?php echo $expense['expense_id']; ?>" 
                                                               name="amount" value="<?php echo htmlspecialchars($expense['amount'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label for="edit_date<?php echo $expense['expense_id']; ?>" class="form-label">Date</label>
                                                    <input type="date" class="form-control" 
                                                           id="edit_date<?php echo $expense['expense_id']; ?>" 
                                                           name="expense_date" 
                                                           value="<?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?>" required>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label for="edit_description<?php echo $expense['expense_id']; ?>" class="form-label">Description</label>
                                                    <textarea class="form-control" 
                                                              id="edit_description<?php echo $expense['expense_id']; ?>" 
                                                              name="description" rows="2"><?php echo htmlspecialchars($expense['description'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Update Expense</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Prevent modal from closing on backdrop click
    $('.modal').modal({
        backdrop: 'static',
        keyboard: false
    });

    // Handle form submission with AJAX
    $('.modal form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var modal = form.closest('.modal');
        
        $.ajax({
            type: 'POST',
            url: 'expenses.php',
            data: form.serialize(),
            success: function(response) {
                // Close the modal
                modal.modal('hide');
                // Reload the page after a short delay
                setTimeout(function() {
                    location.reload();
                }, 500);
            },
            error: function() {
                alert('An error occurred while updating the expense.');
            }
        });
    });

    // Fix modal backdrop issues
    $('.modal').on('shown.bs.modal', function() {
        if($('.modal-backdrop').length > 1) {
            $('.modal-backdrop').not(':first').remove();
        }
    });

    // Clean up modal backdrop on close
    $('.modal').on('hidden.bs.modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>

<style>
/* Fix modal backdrop and animation */
.modal {
    background: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
    display: none;
}

.modal.fade .modal-dialog {
    transition: transform .3s ease-out;
    transform: translate(0, -25%);
}

.modal.show .modal-dialog {
    transform: translate(0, 0);
}

/* Prevent content shift when modal opens */
body.modal-open {
    overflow: hidden;
    padding-right: 0 !important;
}

/* Smooth transition for modal */
.modal-content {
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Fix z-index issues */
.modal {
    z-index: 1050;
}

.modal-backdrop {
    z-index: 1040;
}

/* Enhance table appearance */
.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.expense-type-badge {
    font-size: 0.9rem;
    padding: 0.4em 0.8em;
}

.amount-text {
    font-weight: 500;
    color: #2563eb;
}

.summary-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-card h6 {
    color: #1e293b;
    margin-bottom: 0.5rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html> 