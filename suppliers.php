<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Supplier Management';
$current_page = 'suppliers';

// Initialize message variables
$success = '';
$error = '';

// Handle supplier operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_supplier') {
        $name = $_POST['name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($name) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)";
                $stmt = prepare_query($sql, [$name, $contact_person, $phone, $email, $address]);
                
                if ($stmt->execute()) {
                    $success = 'Supplier added successfully';
                } else {
                    $error = 'Failed to add supplier';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while adding supplier';
                error_log("Supplier Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'update_supplier') {
        $supplier_id = $_POST['supplier_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($supplier_id) || empty($name) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $sql = "UPDATE suppliers 
                        SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? 
                        WHERE supplier_id = ?";
                $stmt = prepare_query($sql, [$name, $contact_person, $phone, $email, $address, $supplier_id]);
                
                if ($stmt->execute()) {
                    $success = 'Supplier updated successfully';
                } else {
                    $error = 'Failed to update supplier';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating supplier';
                error_log("Supplier Error: " . $e->getMessage());
            }
        }
    }
}

// Get all suppliers with their purchase statistics
$suppliers_query = "
    SELECT s.*, 
           COUNT(DISTINCT p.purchase_id) as total_purchases,
           COALESCE(SUM(p.total_amount), 0) as total_purchase_amount
    FROM suppliers s
    LEFT JOIN purchases p ON s.supplier_id = p.supplier_id
    GROUP BY s.supplier_id
    ORDER BY s.supplier_id ASC";
$suppliers_result = $conn->query($suppliers_query);

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="supplier-search">
            <input type="text" class="form-control" id="supplierSearch" placeholder="Search suppliers...">
        </div>
    </div>

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

    <!-- Add New Supplier -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Supplier</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="action" value="add_supplier">
                
                <div class="col-md-4">
                    <label for="name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="col-md-4">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person">
                </div>
                
                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="col-md-8">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Supplier List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Supplier List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Contact Info</th>
                            <th>Address</th>
                            <th>Purchase History</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($supplier['supplier_id']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                                <td>
                                    <div>
                                        <i class="fas fa-phone me-1"></i>
                                        <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($supplier['phone']); ?>
                                        </a>
                                    </div>
                                    <?php if ($supplier['email']): ?>
                                        <div>
                                            <i class="fas fa-envelope me-1"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($supplier['email']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['address'] ?: 'N/A'); ?></td>
                                <td>
                                    <div class="purchase-stats">
                                        <div>Total Purchases: <?php echo number_format($supplier['total_purchases']); ?></div>
                                        <div>Total Amount: Rs. <?php echo number_format($supplier['total_purchase_amount'], 2); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $supplier['supplier_id']; ?>"
                                                title="Edit Supplier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="purchases.php?supplier_id=<?php echo $supplier['supplier_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Purchase History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $supplier['supplier_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Supplier</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="" class="row g-3">
                                                <input type="hidden" name="action" value="update_supplier">
                                                <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_name<?php echo $supplier['supplier_id']; ?>" class="form-label">Company Name</label>
                                                    <input type="text" class="form-control" id="edit_name<?php echo $supplier['supplier_id']; ?>" 
                                                           name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_contact_person<?php echo $supplier['supplier_id']; ?>" class="form-label">Contact Person</label>
                                                    <input type="text" class="form-control" id="edit_contact_person<?php echo $supplier['supplier_id']; ?>" 
                                                           name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_phone<?php echo $supplier['supplier_id']; ?>" class="form-label">Phone</label>
                                                    <input type="text" class="form-control" id="edit_phone<?php echo $supplier['supplier_id']; ?>" 
                                                           name="phone" value="<?php echo htmlspecialchars($supplier['phone']); ?>" required>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="edit_email<?php echo $supplier['supplier_id']; ?>" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="edit_email<?php echo $supplier['supplier_id']; ?>" 
                                                           name="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label for="edit_address<?php echo $supplier['supplier_id']; ?>" class="form-label">Address</label>
                                                    <input type="text" class="form-control" id="edit_address<?php echo $supplier['supplier_id']; ?>" 
                                                           name="address" value="<?php echo htmlspecialchars($supplier['address'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Update Supplier</button>
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
            url: 'suppliers.php',
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
                alert('An error occurred while updating the supplier.');
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

    // Supplier search functionality
    $('#supplierSearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('tbody tr').each(function() {
            const name = $(this).find('td:eq(1)').text().toLowerCase();
            const contact = $(this).find('td:eq(2)').text().toLowerCase();
            const phone = $(this).find('td:eq(3)').text().toLowerCase();
            
            if (name.includes(searchText) || contact.includes(searchText) || phone.includes(searchText)) {
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

.purchase-stats {
    font-size: 0.9rem;
    color: #666;
}

.purchase-stats div {
    margin-bottom: 0.2rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
