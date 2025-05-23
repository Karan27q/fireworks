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

// Handle product deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productId = $_GET['delete'];
    
    // Get product image for deletion
    $imageQuery = "SELECT image FROM products WHERE id = $productId";
    $imageResult = mysqli_query($conn, $imageQuery);
    $product = mysqli_fetch_assoc($imageResult);
    
    // Delete product
    $deleteQuery = "DELETE FROM products WHERE id = $productId";
    $deleteResult = mysqli_query($conn, $deleteQuery);
    
    if($deleteResult) {
        // Delete product image if exists
        if($product['image'] && file_exists("../uploads/products/{$product['image']}")) {
            unlink("../uploads/products/{$product['image']}");
        }
        
        $successMessage = "Product deleted successfully";
    } else {
        $errorMessage = "Failed to delete product";
    }
}

// Set up pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Set up filtering
$whereClause = "";
$filterParams = [];

if(isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    if($filter === 'active') {
        $whereClause = "WHERE p.active = 1";
    } elseif($filter === 'inactive') {
        $whereClause = "WHERE p.active = 0";
    } elseif($filter === 'low_stock') {
        $whereClause = "WHERE p.stock_quantity <= 10";
    }
    
    $filterParams[] = "filter=$filter";
}

// Set up search
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    
    if(empty($whereClause)) {
        $whereClause = "WHERE p.name LIKE '%$search%' OR p.description LIKE '%$search%'";
    } else {
        $whereClause .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
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

if (!$countResult) {
    $errorMessage = "Database error: " . mysqli_error($conn);
    $totalProducts = 0;
    $totalPages = 0;
} else {
    $totalProducts = mysqli_fetch_assoc($countResult)['total'];
    $totalPages = ceil($totalProducts / $limit);
}

// Get products
$productsQuery = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $whereClause 
                 ORDER BY p.id DESC 
                 LIMIT $offset, $limit";
$productsResult = mysqli_query($conn, $productsQuery);

if (!$productsResult) {
    $errorMessage = "Database error: " . mysqli_error($conn);
    $products = [];
} else {
    $products = mysqli_fetch_all($productsResult, MYSQLI_ASSOC);
}

// Get all categories for filter dropdown
$categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

if (!$categoriesResult) {
    $errorMessage = "Database error: " . mysqli_error($conn);
    $categories = [];
} else {
    $categories = mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            transition: margin-left 0.3s;
        }

        .main-content.sidebar-collapsed {
            margin-left: 60px;
        }

        .content-wrapper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .content-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .data-table img {
            max-width: 50px;
            height: auto;
            border-radius: 4px;
        }

        .stock-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .stock-critical {
            background-color: #dc3545;
            color: white;
        }

        .stock-low {
            background-color: #ffc107;
            color: #000;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-delivered {
            background-color: #28a745;
            color: white;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination-link:hover {
            background-color: #f8f9fa;
        }

        .pagination-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .text-center {
            text-align: center;
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
            
            <!-- Products Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Products</h1>
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
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
                                <option value="active" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="low_stock" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
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
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($products) > 0): ?>
                                <?php foreach($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <?php if(!empty($product['image']) && file_exists("../uploads/products/{$product['image']}")): ?>
                                                <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" width="50">
                                            <?php else: ?>
                                                <img src="../uploads/products/no-image.png" alt="No Image" width="50">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <?php if($product['stock_quantity'] <= 5): ?>
                                                <span class="stock-badge stock-critical"><?php echo $product['stock_quantity']; ?></span>
                                            <?php elseif($product['stock_quantity'] <= 10): ?>
                                                <span class="stock-badge stock-low"><?php echo $product['stock_quantity']; ?></span>
                                            <?php else: ?>
                                                <?php echo $product['stock_quantity']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($product['active']): ?>
                                                <span class="status-badge status-delivered">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-cancelled">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No products found</td>
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
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
