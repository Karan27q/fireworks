<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Handle stock adjustment
if(isset($_POST['adjust_stock'])) {
    $productId = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Get current stock
    $stockQuery = "SELECT stock_quantity FROM products WHERE id = $productId";
    $stockResult = mysqli_query($conn, $stockQuery);
    
    if(mysqli_num_rows($stockResult) === 1) {
        $currentStock = mysqli_fetch_assoc($stockResult)['stock_quantity'];
        $newStock = $currentStock + $adjustment;
        
        // Ensure stock doesn't go below 0
        if($newStock < 0) {
            $errorMessage = "Stock cannot be negative. Maximum deduction allowed: $currentStock";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Update product stock
                $updateQuery = "UPDATE products SET stock_quantity = $newStock WHERE id = $productId";
                $updateResult = mysqli_query($conn, $updateQuery);
                
                if(!$updateResult) {
                    throw new Exception("Failed to update stock: " . mysqli_error($conn));
                }
                
                // Log the adjustment
                $logQuery = "INSERT INTO inventory_log (product_id, admin_id, previous_quantity, new_quantity, adjustment, reason, created_at) 
                            VALUES ($productId, $adminId, $currentStock, $newStock, $adjustment, '$reason', NOW())";
                $logResult = mysqli_query($conn, $logQuery);
                
                if(!$logResult) {
                    throw new Exception("Failed to log adjustment: " . mysqli_error($conn));
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                $successMessage = "Stock adjusted successfully";
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $errorMessage = $e->getMessage();
            }
        }
    } else {
        $errorMessage = "Product not found";
    }
}

// Handle bulk stock update
if(isset($_POST['bulk_update'])) {
    $productIds = $_POST['bulk_product_ids'];
    $adjustments = $_POST['bulk_adjustments'];
    $reason = mysqli_real_escape_string($conn, $_POST['bulk_reason']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        $successCount = 0;
        
        foreach($productIds as $index => $productId) {
            if(isset($adjustments[$index]) && $adjustments[$index] !== '') {
                $productId = (int)$productId;
                $adjustment = (int)$adjustments[$index];
                
                // Get current stock
                $stockQuery = "SELECT stock_quantity FROM products WHERE id = $productId";
                $stockResult = mysqli_query($conn, $stockQuery);
                
                if(mysqli_num_rows($stockResult) === 1) {
                    $currentStock = mysqli_fetch_assoc($stockResult)['stock_quantity'];
                    $newStock = $currentStock + $adjustment;
                    
                    // Ensure stock doesn't go below 0
                    if($newStock >= 0) {
                        // Update product stock
                        $updateQuery = "UPDATE products SET stock_quantity = $newStock WHERE id = $productId";
                        $updateResult = mysqli_query($conn, $updateQuery);
                        
                        if(!$updateResult) {
                            throw new Exception("Failed to update stock for product ID $productId: " . mysqli_error($conn));
                        }
                        
                        // Log the adjustment
                        $logQuery = "INSERT INTO inventory_log (product_id, admin_id, previous_quantity, new_quantity, adjustment, reason, created_at) 
                                    VALUES ($productId, $adminId, $currentStock, $newStock, $adjustment, '$reason', NOW())";
                        $logResult = mysqli_query($conn, $logQuery);
                        
                        if(!$logResult) {
                            throw new Exception("Failed to log adjustment for product ID $productId: " . mysqli_error($conn));
                        }
                        
                        $successCount++;
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $successMessage = "$successCount products updated successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $errorMessage = $e->getMessage();
    }
}

// Set up pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Set up filtering
$whereClause = "";
$filterParams = [];

if(isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    if($filter === 'low_stock') {
        $whereClause = "WHERE p.stock_quantity <= 10";
    } elseif($filter === 'out_of_stock') {
        $whereClause = "WHERE p.stock_quantity = 0";
    } elseif($filter === 'in_stock') {
        $whereClause = "WHERE p.stock_quantity > 0";
    }
    
    $filterParams[] = "filter=$filter";
}

// Set up search
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    
    if(empty($whereClause)) {
        $whereClause = "WHERE (p.name LIKE '%$search%' OR p.sku LIKE '%$search%')";
    } else {
        $whereClause .= " AND (p.name LIKE '%$search%' OR p.sku LIKE '%$search%')";
    }
    
    $filterParams[] = "search=$search";
}

// Set up category filter
if(isset($_GET['category']) && is_numeric($_GET['category'])) {
    $categoryId = (int)$_GET['category'];
    
    if(empty($whereClause)) {
        $whereClause = "WHERE p.category_id = $categoryId";
    } else {
        $whereClause .= " AND p.category_id = $categoryId";
    }
    
    $filterParams[] = "category=$categoryId";
}

// Count total products
$countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalProducts = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products
$productsQuery = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $whereClause 
                 ORDER BY p.name ASC 
                 LIMIT $offset, $limit";
$productsResult = mysqli_query($conn, $productsQuery);
$products = mysqli_fetch_all($productsResult, MYSQLI_ASSOC);

// Get all categories for filter dropdown
$categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC);

// Get recent inventory logs
$logsQuery = "SELECT il.*, p.name as product_name, a.name as admin_name 
             FROM inventory_log il 
             JOIN products p ON il.product_id = p.id 
             JOIN admins a ON il.admin_id = a.id 
             ORDER BY il.created_at DESC 
             LIMIT 10";
$logsResult = mysqli_query($conn, $logsQuery);
$logs = mysqli_fetch_all($logsResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .inventory-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stock-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .stock-normal {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-critical {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .adjustment-form {
            margin-bottom: 20px;
        }
        
        .adjustment-form .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .adjustment-form .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .bulk-update-container {
            margin-top: 30px;
        }
        
        .bulk-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .bulk-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .bulk-row .remove-row {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 16px;
        }
        
        .add-row-btn {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            padding: 0;
            margin-bottom: 15px;
        }
        
        .add-row-btn i {
            margin-right: 5px;
        }
        
        .log-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .log-item .log-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .log-item .log-product {
            font-weight: bold;
        }
        
        .log-item .log-date {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .log-item .log-details {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .log-item .log-adjustment {
            font-weight: bold;
        }
        
        .log-item .log-adjustment.positive {
            color: #2ecc71;
        }
        
        .log-item .log-adjustment.negative {
            color: #e74c3c;
        }
        
        .log-item .log-reason {
            margin-top: 5px;
            font-style: italic;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Inventory Management Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Inventory Management</h1>
                </div>
                
                <?php if(isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <div class="inventory-grid">
                    <!-- Products Inventory -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Products Inventory</h2>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="filters">
                                <form action="" method="GET" class="filter-form">
                                    <div class="filter-group">
                                        <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        <button type="submit" class="btn-sm">Search</button>
                                    </div>
                                    
                                    <div class="filter-group">
                                        <select name="filter" onchange="this.form.submit()">
                                            <option value="">All Products</option>
                                            <option value="in_stock" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                                            <option value="low_stock" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
                                            <option value="out_of_stock" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                        </select>
                                    </div>
                                    
                                    <div class="filter-group">
                                        <select name="category" onchange="this.form.submit()">
                                            <option value="">All Categories</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $category['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Products Table -->
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>SKU</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($products) > 0): ?>
                                            <?php foreach($products as $product): ?>
                                                <tr>
                                                    <td><?php echo $product['id']; ?></td>
                                                    <td>
                                                        <div class="product-info">
                                                            <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" width="40">
                                                            <span><?php echo $product['name']; ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $product['category_name']; ?></td>
                                                    <td><?php echo $product['sku'] ?: '-'; ?></td>
                                                    <td>
                                                        <?php
                                                        $stockClass = 'stock-normal';
                                                        if($product['stock_quantity'] <= 0) {
                                                            $stockClass = 'stock-out';
                                                        } elseif($product['stock_quantity'] <= 5) {
                                                            $stockClass = 'stock-critical';
                                                        } elseif($product['stock_quantity'] <= 10) {
                                                            $stockClass = 'stock-low';
                                                        }
                                                        ?>
                                                        <span class="stock-badge <?php echo $stockClass; ?>">
                                                            <?php echo $product['stock_quantity']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn-sm btn-primary adjust-stock-btn" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo $product['name']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                                            <i class="fas fa-edit"></i> Adjust
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No products found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php
                                    $queryParams = $filterParams;
                                    
                                    // Previous page link
                                    if($page > 1) {
                                        $queryParams[] = "page=" . ($page - 1);
                                        echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">&laquo; Previous</a>';
                                    }
                                    
                                    // Page links
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for($i = $startPage; $i <= $endPage; $i++) {
                                        $queryParams = $filterParams;
                                        $queryParams[] = "page=$i";
                                        
                                        if($i == $page) {
                                            echo '<span class="pagination-link active">' . $i . '</span>';
                                        } else {
                                            echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Next page link
                                    if($page < $totalPages) {
                                        $queryParams = $filterParams;
                                        $queryParams[] = "page=" . ($page + 1);
                                        echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">Next &raquo;</a>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Bulk Update Form -->
                            <div class="bulk-update-container">
                                <h3>Bulk Stock Update</h3>
                                <form action="" method="POST" id="bulk-update-form">
                                    <div id="bulk-rows-container">
                                        <div class="bulk-row">
                                            <div class="form-group">
                                                <select name="bulk_product_ids[]" class="form-control" required>
                                                    <option value="">Select Product</option>
                                                    <?php
                                                    // Get all products for dropdown
                                                    $allProductsQuery = "SELECT id, name, stock_quantity FROM products ORDER BY name ASC";
                                                    $allProductsResult = mysqli_query($conn, $allProductsQuery);
                                                    $allProducts = mysqli_fetch_all($allProductsResult, MYSQLI_ASSOC);
                                                    
                                                    foreach($allProducts as $prod) {
                                                        echo '<option value="' . $prod['id'] . '">' . $prod['name'] . ' (Current: ' . $prod['stock_quantity'] . ')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input type="number" name="bulk_adjustments[]" class="form-control" placeholder="Adjustment (+/-)" required>
                                            </div>
                                            <button type="button" class="remove-row"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                    
                                    <button type="button" id="add-row-btn" class="add-row-btn">
                                        <i class="fas fa-plus"></i> Add Another Product
                                    </button>
                                    
                                    <div class="form-group">
                                        <label for="bulk_reason">Reason for Adjustment</label>
                                        <textarea id="bulk_reason" name="bulk_reason" class="form-control" rows="2" required></textarea>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="bulk_update" class="btn btn-primary">Update Stock</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Recent Inventory Activity</h2>
                        </div>
                        <div class="card-body">
                            <?php if(count($logs) > 0): ?>
                                <div class="inventory-logs">
                                    <?php foreach($logs as $log): ?>
                                        <div class="log-item">
                                            <div class="log-header">
                                                <span class="log-product"><?php echo $log['product_name']; ?></span>
                                                <span class="log-date"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></span>
                                            </div>
                                            <div class="log-details">
                                                <span>
                                                    <?php echo $log['previous_quantity']; ?> → <?php echo $log['new_quantity']; ?>
                                                </span>
                                                <span class="log-adjustment <?php echo $log['adjustment'] >= 0 ? 'positive' : 'negative'; ?>">
                                                    <?php echo $log['adjustment'] >= 0 ? '+' . $log['adjustment'] : $log['adjustment']; ?>
                                                </span>
                                            </div>
                                            <div class="log-reason">
                                                "<?php echo $log['reason']; ?>" - by <?php echo $log['admin_name']; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Stock Adjustment Modal -->
    <div class="modal" id="adjustStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Adjust Stock</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST" class="adjustment-form">
                    <input type="hidden" id="product_id" name="product_id">
                    
                    <div class="form-group">
                        <label for="product_name">Product</label>
                        <input type="text" id="product_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_stock">Current Stock</label>
                            <input type="number" id="current_stock" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="adjustment">Adjustment (+/-)</label>
                            <input type="number" id="adjustment" name="adjustment" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_stock">New Stock</label>
                            <input type="number" id="new_stock" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Adjustment</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="adjust_stock" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Stock Adjustment Modal
            const modal = document.getElementById('adjustStockModal');
            const adjustButtons = document.querySelectorAll('.adjust-stock-btn');
            const closeButtons = document.querySelectorAll('.close, .close-modal');
            
            // Open modal when adjust button is clicked
            adjustButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const currentStock = parseInt(this.getAttribute('data-stock'));
                    
                    document.getElementById('product_id').value = productId;
                    document.getElementById('product_name').value = productName;
                    document.getElementById('current_stock').value = currentStock;
                    document.getElementById('adjustment').value = '';
                    document.getElementById('new_stock').value = '';
                    document.getElementById('reason').value = '';
                    
                    modal.style.display = 'block';
                });
            });
            
            // Close modal when close button is clicked
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside the modal
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Calculate new stock when adjustment is changed
            const adjustmentInput = document.getElementById('adjustment');
            const currentStockInput = document.getElementById('current_stock');
            const newStockInput = document.getElementById('new_stock');
            
            adjustmentInput.addEventListener('input', function() {
                const currentStock = parseInt(currentStockInput.value) || 0;
                const adjustment = parseInt(this.value) || 0;
                const newStock = currentStock + adjustment;
                
                newStockInput.value = newStock;
                
                // Highlight negative stock in red
                if(newStock < 0) {
                    newStockInput.style.color = '#e74c3c';
                } else {
                    newStockInput.style.color = '';
                }
            });
            
            // Bulk Update Form
            const addRowBtn = document.getElementById('add-row-btn');
            const bulkRowsContainer = document.getElementById('bulk-rows-container');
            
            // Add new row
            addRowBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'bulk-row';
                newRow.innerHTML = `
                    <div class="form-group">
                        <select name="bulk_product_ids[]" class="form-control" required>
                            <option value="">Select Product</option>
                            <?php
                            foreach($allProducts as $prod) {
                                echo '<option value="' . $prod['id'] . '">' . $prod['name'] . ' (Current: ' . $prod['stock_quantity'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="bulk_adjustments[]" class="form-control" placeholder="Adjustment (+/-)" required>
                    </div>
                    <button type="button" class="remove-row"><i class="fas fa-times"></i></button>
                `;
                
                bulkRowsContainer.appendChild(newRow);
                
                // Add event listener to remove button
                const removeBtn = newRow.querySelector('.remove-row');
                removeBtn.addEventListener('click', function() {
                    bulkRowsContainer.removeChild(newRow);
                });
            });
            
            // Remove row
            document.querySelectorAll('.remove-row').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.parentElement;
                    if(bulkRowsContainer.children.length > 1) {
                        bulkRowsContainer.removeChild(row);
                    }
                });
            });
        });
    </script>
</body>
</html>
