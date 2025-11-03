<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$page_title = 'Employee Management';
$current_page = 'employees';

// Initialize message variables
$success = '';
$error = '';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/header.php';

// Handle employee operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_employee') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $salary = $_POST['salary'] ?? '';
        $assigned_pump = $_POST['assigned_pump'] ?? null;
        
        if (empty($name) || empty($email) || empty($phone) || empty($salary)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "INSERT INTO attendants (name, email, phone, address, salary, joining_date, assigned_pump, status) 
                        VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'active')";
                $stmt = prepare_query($sql, [$name, $email, $phone, $address, $salary, $assigned_pump]);
                
                if ($stmt->execute()) {
                    $success = 'Employee added successfully';
                } else {
                    $error = 'Failed to add employee';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while adding employee';
                error_log("Employee Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'update_employee') {
        $attendant_id = $_POST['attendant_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $salary = $_POST['salary'] ?? '';
        $assigned_pump = $_POST['assigned_pump'] ?? null;
        $status = $_POST['status'] ?? 'active';
        
        if (empty($attendant_id) || empty($name) || empty($email) || empty($phone) || empty($salary)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // Convert empty assigned_pump to NULL
                $assigned_pump = empty($assigned_pump) ? null : $assigned_pump;
                
                $sql = "UPDATE attendants 
                        SET name = ?, email = ?, phone = ?, address = ?, 
                            salary = ?, assigned_pump = ?, status = ? 
                        WHERE attendant_id = ?";
                $stmt = prepare_query($sql);
                $stmt->bind_param("ssssdssi", $name, $email, $phone, $address, $salary, 
                                          $assigned_pump, $status, $attendant_id);
                
                if ($stmt->execute()) {
                    $success = 'Employee updated successfully';
                } else {
                    $error = 'Failed to update employee: ' . $stmt->error;
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating employee: ' . $e->getMessage();
                error_log("Employee Update Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'update_status') {
        $attendant_id = $_POST['attendant_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($attendant_id) || empty($status)) {
            $error = 'Invalid request';
        } else {
            try {
                $sql = "UPDATE attendants SET status = ? WHERE attendant_id = ?";
                $stmt = prepare_query($sql, [$status, $attendant_id]);
                
                if ($stmt->execute()) {
                    $success = 'Employee status updated successfully';
                } else {
                    $error = 'Failed to update employee status';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating employee status';
                error_log("Employee Error: " . $e->getMessage());
            }
        }
    }
}

// Get all pumps for dropdown
$pumps_query = "SELECT pump_id, pump_name, status FROM fuel_pumps WHERE status = 'active'";
$pumps_result = $conn->query($pumps_query);
$available_pumps = [];
while ($pump = $pumps_result->fetch_assoc()) {
    $available_pumps[] = $pump;
}

// Get all employees with their assigned pump details
$employees_query = "SELECT a.*, fp.pump_name 
                   FROM attendants a 
                   LEFT JOIN fuel_pumps fp ON a.assigned_pump = fp.pump_id 
                   ORDER BY a.attendant_id ASC";
$employees_result = $conn->query($employees_query);
?>

<div class="container mt-4">
    

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Add New Employee -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Employee</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="add_employee">
                
                <div class="col-md-4">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                
                <div class="col-md-4">
                    <label for="salary" class="form-label">Salary</label>
                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" required>
                </div>
                
                <div class="col-md-4">
                    <label for="assigned_pump" class="form-label">Assigned Pump</label>
                    <select class="form-select" id="assigned_pump" name="assigned_pump">
                        <option value="">None</option>
                        <?php foreach ($available_pumps as $pump): ?>
                            <option value="<?php echo $pump['pump_id']; ?>">
                                <?php echo htmlspecialchars($pump['pump_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="1"></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Employee List</h5>
            <a href="attendance.php" class="btn btn-primary btn-sm">
                <i class="fas fa-calendar-check me-1"></i>Mark Attendance
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned Pump</th>
                            <th>Salary</th>
                            <th>Joining Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees_result->fetch_assoc()): ?>
                            <tr class="<?php echo $employee['status'] === 'inactive' ? 'inactive-employee' : ''; ?>">
                                <td><?php echo htmlspecialchars($employee['attendant_id']); ?></td>
                                <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                <td><?php echo $employee['pump_name'] ? htmlspecialchars($employee['pump_name']) : 'Not Assigned'; ?></td>
                                <td><?php echo number_format($employee['salary'], 2); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($employee['joining_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $employee['attendant_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="attendant_id" value="<?php echo $employee['attendant_id']; ?>">
                                        <input type="hidden" name="status" 
                                               value="<?php echo $employee['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $employee['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>"
                                                onclick="return confirm('Are you sure you want to <?php echo $employee['status'] === 'active' ? 'deactivate' : 'activate'; ?> this employee?')">
                                            <i class="fas <?php echo $employee['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $employee['attendant_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Employee</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="" class="needs-validation" novalidate>
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update_employee">
                                                <input type="hidden" name="attendant_id" value="<?php echo $employee['attendant_id']; ?>">
                                                
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="edit_name<?php echo $employee['attendant_id']; ?>" class="form-label">Name</label>
                                                        <input type="text" class="form-control" id="edit_name<?php echo $employee['attendant_id']; ?>" 
                                                               name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_email<?php echo $employee['attendant_id']; ?>" class="form-label">Email</label>
                                                        <input type="email" class="form-control" id="edit_email<?php echo $employee['attendant_id']; ?>" 
                                                               name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_phone<?php echo $employee['attendant_id']; ?>" class="form-label">Phone</label>
                                                        <input type="text" class="form-control" id="edit_phone<?php echo $employee['attendant_id']; ?>" 
                                                               name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_salary<?php echo $employee['attendant_id']; ?>" class="form-label">Salary</label>
                                                        <input type="number" class="form-control" id="edit_salary<?php echo $employee['attendant_id']; ?>" 
                                                               name="salary" value="<?php echo $employee['salary']; ?>" step="0.01" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_assigned_pump<?php echo $employee['attendant_id']; ?>" class="form-label">Assigned Pump</label>
                                                        <select class="form-select" id="edit_assigned_pump<?php echo $employee['attendant_id']; ?>" name="assigned_pump">
                                                            <option value="">None</option>
                                                            <?php foreach ($available_pumps as $pump): ?>
                                                                <option value="<?php echo $pump['pump_id']; ?>" 
                                                                        <?php echo $employee['assigned_pump'] == $pump['pump_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($pump['pump_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="edit_status<?php echo $employee['attendant_id']; ?>" class="form-label">Status</label>
                                                        <select class="form-select" id="edit_status<?php echo $employee['attendant_id']; ?>" name="status">
                                                            <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label for="edit_address<?php echo $employee['attendant_id']; ?>" class="form-label">Address</label>
                                                        <textarea class="form-control" id="edit_address<?php echo $employee['attendant_id']; ?>" 
                                                                  name="address" rows="2"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Update Employee</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
            url: 'employees.php',
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
                alert('An error occurred while updating the employee.');
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
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
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
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
