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

// Set up pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Set up filtering
$whereClause = "";
$filterParams = [];

if(isset($_GET['status']) && !empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $whereClause = "WHERE o.status = '$status'";
    $filterParams[] = "status=$status";
}

// Set up search
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    
    if(empty($whereClause)) {
        $whereClause = "WHERE (o.id LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
    } else {
        $whereClause .= " AND (o.id LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    
    $filterParams[] = "search=$search";
}

// Count total orders
$countQuery = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalOrders = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalOrders / $limit);

// Get orders
$ordersQuery = "SELECT o.*, u.name as customer_name, u.email as customer_email 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               $whereClause 
               ORDER BY o.created_at DESC 
               LIMIT $offset, $limit";
$ordersResult = mysqli_query($conn, $ordersQuery);
$orders = mysqli_fetch_all($ordersResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Panel</title>
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
            
            <!-- Orders Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Orders</h1>
                </div>
                
                <!-- Filters -->
                <div class="filters">
                    <form action="" method="GET" class="filter-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Search orders..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="btn-sm">Search</button>
                        </div>
                        
                        <div class="filter-group">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All Orders</option>
                                <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo (isset($_GET['status']) && $_GET['status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($orders) > 0): ?>
                                <?php foreach($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo $order['customer_name']; ?></strong>
                                                <div><?php echo $order['customer_email']; ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo ucfirst($order['payment_method']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No orders found</td>
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
