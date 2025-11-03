<?php
// Include config first
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Set page title and current page for header
$page_title = 'Inventory Management';
$current_page = 'inventory';

// Include header after setting variables it needs
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <ul class="nav nav-tabs card-header-tabs" id="inventoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="fuel-tab" data-bs-toggle="tab" data-bs-target="#fuel" type="button" role="tab" aria-controls="fuel" aria-selected="true">
                        <i class="fas fa-gas-pump me-2"></i>Fuel Inventory
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                        <i class="fas fa-box me-2"></i>Products Inventory
                    </button>
                </li>
            </ul>
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i>Add New Product
            </button>
        </div>
        <div class="card-body">
            <div class="tab-content" id="inventoryTabContent">
                <!-- Fuel Inventory Tab -->
                <div class="tab-pane fade show active" id="fuel" role="tabpanel" aria-labelledby="fuel-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Fuel Type</th>
                                    <th>Current Stock (L)</th>
                                    <th>Purchase Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = prepare_query("SELECT * FROM products WHERE product_type = 'fuel' ORDER BY product_name");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($fuel = $result->fetch_assoc()) {
                                    $status_class = $fuel['stock_quantity'] <= $fuel['reorder_level'] ? 'text-danger' : 'text-success';
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($fuel['product_name']) . "</td>";
                                    echo "<td>" . number_format($fuel['stock_quantity'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($fuel['purchase_price'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($fuel['selling_price'], 2) . "</td>";
                                    echo "<td><span class='" . $status_class . "'><i class='fas fa-circle me-2'></i>" . 
                                         ($fuel['stock_quantity'] <= $fuel['reorder_level'] ? 'Low Stock' : 'Available') . "</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Products Inventory Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <div class="mb-4">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="productSearch" class="form-control search-input" placeholder="Search products by name...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Purchase Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php
                                $stmt = prepare_query("SELECT * FROM products WHERE product_type = 'non-fuel' ORDER BY category, product_name");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($product = $result->fetch_assoc()) {
                                    $status_class = $product['stock_quantity'] <= $product['reorder_level'] ? 'text-danger' : 'text-success';
                                    echo "<tr class='product-row'>";
                                    echo "<td class='product-name'>" . htmlspecialchars($product['product_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($product['category']) . "</td>";
                                    echo "<td>" . $product['stock_quantity'] . " " . htmlspecialchars($product['unit']) . "</td>";
                                    echo "<td>Rs. " . number_format($product['purchase_price'], 2) . "</td>";
                                    echo "<td>Rs. " . number_format($product['selling_price'], 2) . "</td>";
                                    echo "<td><span class='" . $status_class . "'><i class='fas fa-circle me-2'></i>" . 
                                         ($product['stock_quantity'] <= $product['reorder_level'] ? 'Low Stock' : 'Available') . "</span></td>";
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

<!-- Add custom styles -->
<style>
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

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.tab-content {
    padding-top: 1rem;
}

.table thead {
    position: sticky;
    top: 0;
    z-index: 1;
}

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

.no-results {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
}

/* Add Product Button Styles */
.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    padding: 0.5rem 1rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-primary i {
    font-size: 1rem;
}

.d-flex.justify-content-between {
    margin-bottom: 1.5rem;
    padding: 0.5rem 0;
}
</style>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('#inventoryTabs button'))
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault()
            tabTrigger.show()
        })
    })

    // Product search functionality
    const searchInput = document.getElementById('productSearch');
    const productRows = document.querySelectorAll('.product-row');
    const productsTableBody = document.getElementById('productsTableBody');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let hasResults = false;

        productRows.forEach(row => {
            const productName = row.querySelector('.product-name').textContent.toLowerCase();
            if (productName.includes(searchTerm)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Show no results message if needed
        const existingNoResults = productsTableBody.querySelector('.no-results');
        if (!hasResults && !existingNoResults) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = '<td colspan="4">No products found matching your search.</td>';
            productsTableBody.appendChild(noResultsRow);
        } else if (hasResults && existingNoResults) {
            existingNoResults.remove();
        }
    });
});

// Function to toggle category field based on product type
function toggleCategoryField() {
    const productType = document.getElementById('product_type').value;
    const categoryField = document.getElementById('categoryField');
    const categoryInput = document.getElementById('category');
    
    if (productType === 'fuel') {
        categoryField.style.display = 'none';
        categoryInput.removeAttribute('required');
        categoryInput.value = '';
    } else {
        categoryField.style.display = 'block';
        categoryInput.setAttribute('required', 'required');
    }
}

// Initialize the category field visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCategoryField();
});
</script>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addProductForm" action="includes/process_product.php" method="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_type" class="form-label">Product Type</label>
                            <select class="form-select" id="product_type" name="product_type" required onchange="toggleCategoryField()">
                                <option value="">Select Product Type</option>
                                <option value="fuel">Fuel</option>
                                <option value="non-fuel">Non-Fuel Product</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="categoryField">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="stock_quantity" class="form-label">Quantity</label>
                            <input type="number" step="1" class="form-control" id="stock_quantity" name="stock_quantity" required>
                        </div>
                        <div class="col-md-6">
                            <label for="purchase_price" class="form-label">Purchase Price (Rs.)</label>
                            <input type="number" step="1" class="form-control" id="purchase_price" name="purchase_price" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="selling_price" class="form-label">Selling Price (Rs.)</label>
                            <input type="number" step="1" class="form-control" id="selling_price" name="selling_price" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html> 