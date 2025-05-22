<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle batch update
if (isset($_POST['batch_update'])) {
    $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
    $update_field = $_POST['update_field'];
    $update_value = $_POST['update_value'];
    
    if (!empty($product_ids) && !empty($update_field)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            $success_count = 0;
            
            // Prepare the appropriate update query based on the field
            switch ($update_field) {
                case 'price':
                case 'sale_price':
                case 'stock':
                    // Numeric fields
                    $update_query = "UPDATE products SET $update_field = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    
                    foreach ($product_ids as $product_id) {
                        mysqli_stmt_bind_param($stmt, "di", $update_value, $product_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_count++;
                        }
                    }
                    break;
                    
                case 'category_id':
                    // Category field
                    $update_query = "UPDATE products SET category_id = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    
                    foreach ($product_ids as $product_id) {
                        mysqli_stmt_bind_param($stmt, "ii", $update_value, $product_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_count++;
                        }
                    }
                    break;
                    
                case 'status':
                case 'is_featured':
                    // Status fields
                    $update_query = "UPDATE products SET $update_field = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    
                    foreach ($product_ids as $product_id) {
                        mysqli_stmt_bind_param($stmt, "si", $update_value, $product_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_count++;
                        }
                    }
                    break;
                    
                case 'price_adjustment':
                    // Special case: adjust price by percentage
                    $percentage = floatval($update_value);
                    $factor = (100 + $percentage) / 100;
                    
                    $update_query = "UPDATE products SET price = price * ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    
                    foreach ($product_ids as $product_id) {
                        mysqli_stmt_bind_param($stmt, "di", $factor, $product_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_count++;
                        }
                    }
                    break;
                    
                default:
                    throw new Exception("Invalid update field selected.");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $message = "Batch update completed. Successfully updated $success_count products.";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error during batch update: " . $e->getMessage();
        }
    } else {
        $error = "Please select products and specify what to update.";
    }
}

// Handle batch delete
if (isset($_POST['batch_delete'])) {
    $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
    
    if (!empty($product_ids)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            $success_count = 0;
            $delete_query = "DELETE FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            
            foreach ($product_ids as $product_id) {
                mysqli_stmt_bind_param($stmt, "i", $product_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_count++;
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $message = "Batch delete completed. Successfully deleted $success_count products.";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error during batch delete: " . $e->getMessage();
        }
    } else {
        $error = "Please select products to delete.";
    }
}

// Get products for the list
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

if (!empty($search)) {
    $search = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $query .= " AND (p.product_name LIKE '$search' OR p.sku LIKE '$search')";
}

if ($category > 0) {
    $query .= " AND p.category_id = $category";
}

if (!empty($status)) {
    $status = mysqli_real_escape_string($conn, $status);
    $query .= " AND p.status = '$status'";
}

$query .= " ORDER BY p.id DESC";
$result = mysqli_query($conn, $query);

// Get categories for filter
$categories = array();
$cat_query = "SELECT id, category_name FROM categories ORDER BY category_name";
$cat_result = mysqli_query($conn, $cat_query);
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Product Batch Update</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Products</h6>
        </div>
        <div class="card-body">
            <form method="get" action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Search:</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Product name or SKU">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select class="form-control" id="category" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="draft" <?php echo ($status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="discontinued" <?php echo ($status == 'discontinued') ? 'selected' : ''; ?>>Discontinued</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search fa-sm"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Batch Update Form -->
    <form method="post" action="" id="batch-form">
        <!-- Products Table Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Products</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                        aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Bulk Actions:</div>
                        <a class="dropdown-item" href="#" id="select-all">Select All</a>
                        <a class="dropdown-item" href="#" id="deselect-all">Deselect All</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="bulk-import-export.php">Import/Export</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%"><input type="checkbox" id="check-all"></th>
                                <th width="5%">ID</th>
                                <th width="15%">Image</th>
                                <th width="25%">Product</th>
                                <th width="10%">Price</th>
                                <th width="10%">Sale Price</th>
                                <th width="10%">Stock</th>
                                <th width="10%">Category</th>
                                <th width="10%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="product_ids[]" value="<?php echo $row['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if (!empty($row['image_path'])): ?>
                                        <img src="<?php echo '../' . $row['image_path']; ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>" class="img-thumbnail" style="max-height: 50px;">
                                        <?php else: ?>
                                        <div class="text-center">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                                        <small>SKU: <?php echo htmlspecialchars($row['sku']); ?></small>
                                    </td>
                                    <td><?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo !empty($row['sale_price']) ? number_format($row['sale_price'], 2) : '-'; ?></td>
                                    <td><?php echo $row['stock']; ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                        <?php elseif ($row['status'] == 'draft'): ?>
                                        <span class="badge badge-warning">Draft</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">Discontinued</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Batch Actions Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Batch Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="update_field">Update Field:</label>
                            <select class="form-control" id="update_field" name="update_field">
                                <option value="">Select Field to Update</option>
                                <option value="price">Price</option>
                                <option value="sale_price">Sale Price</option>
                                <option value="stock">Stock</option>
                                <option value="category_id">Category</option>
                                <option value="status">Status</option>
                                <option value="is_featured">Featured</option>
                                <option value="price_adjustment">Price Adjustment (%)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="update_value">New Value:</label>
                            
                            <!-- Default input (for price, sale_price, stock) -->
                            <input type="text" class="form-control field-input" id="default_value" name="update_value" placeholder="Enter new value" style="display: none;">
                            
                            <!-- Category dropdown -->
                            <select class="form-control field-input" id="category_value" name="update_value" style="display: none;">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Status dropdown -->
                            <select class="form-control field-input" id="status_value" name="update_value" style="display: none;">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                            
                            <!-- Featured dropdown -->
                            <select class="form-control field-input" id="featured_value" name="update_value" style="display: none;">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                            
                            <!-- Price adjustment input -->
                            <div class="input-group field-input" id="price_adjustment_value" style="display: none;">
                                <input type="text" class="form-control" name="update_value" placeholder="Enter percentage">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="btn-group btn-block">
                                <button type="submit" name="batch_update" class="btn btn-primary" id="update-btn" disabled>
                                    <i class="fas fa-sync-alt fa-sm"></i> Update Selected
                                </button>
                                <button type="button" class="btn btn-danger" id="delete-btn" disabled data-toggle="modal" data-target="#deleteModal">
                                    <i class="fas fa-trash fa-sm"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Select products from the list above, choose what to update, and click "Update Selected".
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete the selected products? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="batch_delete" class="btn btn-danger">Delete Products</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle field selection change
    document.getElementById('update_field').addEventListener('change', function() {
        // Hide all input fields
        document.querySelectorAll('.field-input').forEach(function(el) {
            el.style.display = 'none';
        });
        
        // Show the appropriate input field based on selection
        const field = this.value;
        if (field === 'category_id') {
            document.getElementById('category_value').style.display = 'block';
        } else if (field === 'status') {
            document.getElementById('status_value').style.display = 'block';
        } else if (field === 'is_featured') {
            document.getElementById('featured_value').style.display = 'block';
        } else if (field === 'price_adjustment') {
            document.getElementById('price_adjustment_value').style.display = 'flex';
        } else if (field) {
            document.getElementById('default_value').style.display = 'block';
        }
    });
    
    // Handle check all checkbox
    document.getElementById('check-all').addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.product-checkbox').forEach(function(checkbox) {
            checkbox.checked = isChecked;
        });
        updateButtonState();
    });
    
    // Handle individual checkboxes
    document.querySelectorAll('.product-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', updateButtonState);
    });
    
    // Handle select all link
    document.getElementById('select-all').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.product-checkbox').forEach(function(checkbox) {
            checkbox.checked = true;
        });
        document.getElementById('check-all').checked = true;
        updateButtonState();
    });
    
    // Handle deselect all link
    document.getElementById('deselect-all').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.product-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        document.getElementById('check-all').checked = false;
        updateButtonState();
    });
    
    // Form validation before submit
    document.getElementById('batch-form').addEventListener('submit', function(e) {
        const updateField = document.getElementById('update_field').value;
        const checkedProducts = document.querySelectorAll('.product-checkbox:checked').length;
        
        if (e.submitter && e.submitter.name === 'batch_update') {
            if (!updateField) {
                e.preventDefault();
                alert('Please select a field to update.');
                return;
            }
            
            if (checkedProducts === 0) {
                e.preventDefault();
                alert('Please select at least one product to update.');
                return;
            }
        }
    });
    
    // Function to update button state based on checkbox selection
    function updateButtonState() {
        const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
        const updateBtn = document.getElementById('update-btn');
        const deleteBtn = document.getElementById('delete-btn');
        
        if (checkedCount > 0) {
            updateBtn.disabled = false;
            deleteBtn.disabled = false;
        } else {
            updateBtn.disabled = true;
            deleteBtn.disabled = true;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
