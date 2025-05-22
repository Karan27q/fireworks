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

// Handle discount deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $discountId = $_GET['delete'];
    
    $deleteQuery = "DELETE FROM discounts WHERE id = $discountId";
    $deleteResult = mysqli_query($conn, $deleteQuery);
    
    if($deleteResult) {
        $successMessage = "Discount deleted successfully";
    } else {
        $errorMessage = "Failed to delete discount";
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
        $whereClause = "WHERE active = 1";
    } elseif($filter === 'inactive') {
        $whereClause = "WHERE active = 0";
    } elseif($filter === 'expired') {
        $whereClause = "WHERE end_date < CURDATE()";
    } elseif($filter === 'upcoming') {
        $whereClause = "WHERE start_date > CURDATE()";
    } elseif($filter === 'current') {
        $whereClause = "WHERE start_date <= CURDATE() AND end_date >= CURDATE()";
    }
    
    $filterParams[] = "filter=$filter";
}

// Set up search
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    
    if(empty($whereClause)) {
        $whereClause = "WHERE (name LIKE '%$search%' OR code LIKE '%$search%' OR description LIKE '%$search%')";
    } else {
        $whereClause .= " AND (name LIKE '%$search%' OR code LIKE '%$search%' OR description LIKE '%$search%')";
    }
    
    $filterParams[] = "search=$search";
}

// Count total discounts
$countQuery = "SELECT COUNT(*) as total FROM discounts $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalDiscounts = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalDiscounts / $limit);

// Get discounts
$discountsQuery = "SELECT * FROM discounts $whereClause ORDER BY id DESC LIMIT $offset, $limit";
$discountsResult = mysqli_query($conn, $discountsQuery);
$discounts = mysqli_fetch_all($discountsResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discounts - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Discounts Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Discounts</h1>
                    <a href="add-discount.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Discount
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
                            <input type="text" name="search" placeholder="Search discounts..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="btn-sm">Search</button>
                        </div>
                        
                        <div class="filter-group">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="">All Discounts</option>
                                <option value="active" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="current" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'current') ? 'selected' : ''; ?>>Current</option>
                                <option value="upcoming" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="expired" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                    </form>
                </div>
                
                <!-- Discounts Table -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min. Order</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($discounts) > 0): ?>
                                <?php foreach($discounts as $discount): ?>
                                    <tr>
                                        <td><?php echo $discount['id']; ?></td>
                                        <td><?php echo $discount['name']; ?></td>
                                        <td><code><?php echo $discount['code']; ?></code></td>
                                        <td>
                                            <?php 
                                            if($discount['discount_type'] === 'percentage') {
                                                echo 'Percentage';
                                            } else {
                                                echo 'Fixed Amount';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if($discount['discount_type'] === 'percentage') {
                                                echo $discount['discount_value'] . '%';
                                            } else {
                                                echo '₹' . number_format($discount['discount_value'], 2);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if($discount['min_order_amount'] > 0) {
                                                echo '₹' . number_format($discount['min_order_amount'], 2);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($discount['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($discount['end_date'])); ?></td>
                                        <td>
                                            <?php
                                            $today = date('Y-m-d');
                                            $startDate = $discount['start_date'];
                                            $endDate = $discount['end_date'];
                                            
                                            if(!$discount['active']) {
                                                echo '<span class="status-badge status-cancelled">Inactive</span>';
                                            } elseif($today < $startDate) {
                                                echo '<span class="status-badge status-pending">Upcoming</span>';
                                            } elseif($today > $endDate) {
                                                echo '<span class="status-badge status-cancelled">Expired</span>';
                                            } else {
                                                echo '<span class="status-badge status-delivered">Active</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="edit-discount.php?id=<?php echo $discount['id']; ?>" class="btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="discounts.php?delete=<?php echo $discount['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this discount?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No discounts found</td>
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
