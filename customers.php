<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Customer Management';
$current_page = 'customers';

// Initialize message variables
$success = '';
$error = '';

// Handle customer operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_customer') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $customer_type = $_POST['customer_type'] ?? 'regular';
        
        if (empty($name) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "INSERT INTO customers (name, email, phone, customer_type) VALUES (?, ?, ?, ?)";
                $stmt = prepare_query($sql, [$name, $email, $phone, $customer_type]);
                
                if ($stmt->execute()) {
                    $success = 'Customer added successfully';
                } else {
                    $error = 'Failed to add customer';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while adding customer';
                error_log("Customer Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'update_customer') {
        $customer_id = $_POST['customer_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $customer_type = $_POST['customer_type'] ?? 'regular';
        
        if (empty($customer_id) || empty($name) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "UPDATE customers 
                        SET name = ?, email = ?, phone = ?, customer_type = ? 
                        WHERE customer_id = ?";
                $stmt = prepare_query($sql, [$name, $email, $phone, $customer_type, $customer_id]);
                
                if ($stmt->execute()) {
                    $success = 'Customer updated successfully';
                } else {
                    $error = 'Failed to update customer';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating customer';
                error_log("Customer Error: " . $e->getMessage());
            }
        }
    }
}

// Get all customers
$customers_query = "SELECT * FROM customers ORDER BY customer_type DESC, name ASC";
$customers_result = $conn->query($customers_query);

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      
        <div class="customer-search">
            
            <input type="text" class="form-control" id="customerSearch" placeholder="Search customers...">
        </div>
    </div>

        <?php if (!empty($success)): ?>        <div class="alert alert-success alert-dismissible fade show">            <i class="fas fa-check-circle me-2"></i>            <?php echo htmlspecialchars($success); ?>            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>        </div>    <?php endif; ?>    <?php if (!empty($error)): ?>        <div class="alert alert-danger alert-dismissible fade show">            <i class="fas fa-exclamation-circle me-2"></i>            <?php echo htmlspecialchars($error); ?>            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>        </div>    <?php endif; ?>

    <!-- Add New Customer -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Customer</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="add_customer">
                
                <div class="col-md-4">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                
                <div class="col-md-4">
                    <label for="customer_type" class="form-label">Customer Type</label>
                    <select class="form-select" id="customer_type" name="customer_type" required>
                        <option value="regular">Regular</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Customer List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($customer = $customers_result->fetch_assoc()): ?>
                            <tr class="<?php echo $customer['customer_type'] === 'commercial' ? 'commercial-customer' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($customer['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($customer['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $customer['customer_type'] === 'commercial' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($customer['customer_type']); ?>
                                    </span>
                                </td>
                                <td>
                                                                        <div class="btn-group">                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"                                                 data-bs-target="#editModal<?php echo $customer['customer_id']; ?>"                                                title="Edit Customer">                                            <i class="fas fa-edit"></i>                                        </button>                                        <a href="customer_details.php?id=<?php echo $customer['customer_id']; ?>"                                            class="btn btn-sm btn-info" title="View Customer Details">                                            <i class="fas fa-info-circle"></i>                                        </a>                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $customer['customer_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Customer</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="" class="row g-3">
                                                <input type="hidden" name="action" value="update_customer">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_name<?php echo $customer['customer_id']; ?>" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="edit_name<?php echo $customer['customer_id']; ?>" 
                                                           name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_email<?php echo $customer['customer_id']; ?>" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="edit_email<?php echo $customer['customer_id']; ?>" 
                                                           name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_phone<?php echo $customer['customer_id']; ?>" class="form-label">Phone</label>
                                                    <input type="text" class="form-control" id="edit_phone<?php echo $customer['customer_id']; ?>" 
                                                           name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_customer_type<?php echo $customer['customer_id']; ?>" class="form-label">Customer Type</label>
                                                    <select class="form-select" id="edit_customer_type<?php echo $customer['customer_id']; ?>" name="customer_type">
                                                        <option value="regular" <?php echo $customer['customer_type'] === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                                        <option value="commercial" <?php echo $customer['customer_type'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Update Customer</button>
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
            url: 'customers.php',
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
                alert('An error occurred while updating the customer.');
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

    // Customer search functionality
    $('#customerSearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('tbody tr').each(function() {
            const name = $(this).find('td:eq(0)').text().toLowerCase();
            const email = $(this).find('td:eq(1)').text().toLowerCase();
            const phone = $(this).find('td:eq(2)').text().toLowerCase();
            
            if (name.includes(searchText) || email.includes(searchText) || phone.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
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

/* Enhance table appearance */
.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.commercial-customer {
    background-color: rgba(13, 110, 253, 0.02);
}
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
