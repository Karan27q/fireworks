<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);

if(!$adminResult || mysqli_num_rows($adminResult) == 0) {
    // Admin not found, clear session and redirect to login
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$admin = mysqli_fetch_assoc($adminResult);

// Get dashboard statistics
$productsQuery = "SELECT COUNT(*) as total FROM products";
$productsResult = mysqli_query($conn, $productsQuery);
$productsCount = mysqli_fetch_assoc($productsResult)['total'];

$categoriesQuery = "SELECT COUNT(*) as total FROM categories";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categoriesCount = mysqli_fetch_assoc($categoriesResult)['total'];

$usersQuery = "SELECT COUNT(*) as total FROM users";
$usersResult = mysqli_query($conn, $usersQuery);
$usersCount = mysqli_fetch_assoc($usersResult)['total'];

$ordersQuery = "SELECT COUNT(*) as total FROM orders";
$ordersResult = mysqli_query($conn, $ordersQuery);
$ordersCount = mysqli_fetch_assoc($ordersResult)['total'];

$pendingOrdersQuery = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$pendingOrdersResult = mysqli_query($conn, $pendingOrdersQuery);
$pendingOrdersCount = mysqli_fetch_assoc($pendingOrdersResult)['total'];

// Get recent orders
$recentOrdersQuery = "SELECT o.*, u.name as customer_name FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC LIMIT 5";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQuery);
$recentOrders = mysqli_fetch_all($recentOrdersResult, MYSQLI_ASSOC);

// Get low stock products
$lowStockQuery = "SELECT * FROM products WHERE stock_quantity <= 10 AND active = 1 ORDER BY stock_quantity ASC LIMIT 5";
$lowStockResult = mysqli_query($conn, $lowStockQuery);
$lowStockProducts = mysqli_fetch_all($lowStockResult, MYSQLI_ASSOC);

// Get recent sales data for chart
$salesQuery = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
              FROM orders 
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
              GROUP BY DATE(created_at) 
              ORDER BY date";
$salesResult = mysqli_query($conn, $salesQuery);
$salesData = mysqli_fetch_all($salesResult, MYSQLI_ASSOC);

// Format sales data for chart
$dates = [];
$sales = [];
foreach($salesData as $data) {
    $dates[] = date('d M', strtotime($data['date']));
    $sales[] = $data['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fireworks Shop</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard">
                <h1>Dashboard</h1>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Products</h3>
                            <p><?php echo $productsCount; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Categories</h3>
                            <p><?php echo $categoriesCount; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Users</h3>
                            <p><?php echo $usersCount; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Orders</h3>
                            <p><?php echo $ordersCount; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Sales Overview</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Orders Overview -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Orders Overview</h2>
                        <a href="orders.php" class="view-all">View All</a>
                    </div>
                    
                    <div class="order-stats">
                        <div class="order-stat">
                            <h3>Pending Orders</h3>
                            <p><?php echo $pendingOrdersCount; ?></p>
                        </div>
                        
                        <?php
                        // Get counts for other order statuses
                        $statuses = ['processing', 'shipped', 'delivered', 'cancelled'];
                        foreach($statuses as $status) {
                            $query = "SELECT COUNT(*) as total FROM orders WHERE status = '$status'";
                            $result = mysqli_query($conn, $query);
                            $count = mysqli_fetch_assoc($result)['total'];
                            
                            echo '<div class="order-stat">';
                            echo '<h3>'.ucfirst($status).' Orders</h3>';
                            echo '<p>'.$count.'</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Recent Orders</h2>
                        <a href="orders.php" class="view-all">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recentOrders) > 0): ?>
                                    <?php foreach($recentOrders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo $order['customer_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn-sm">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Low Stock Products -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Low Stock Products</h2>
                        <a href="products.php?filter=low_stock" class="view-all">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($lowStockProducts) > 0): ?>
                                    <?php foreach($lowStockProducts as $product): ?>
                                        <?php
                                        // Get category name
                                        $catQuery = "SELECT name FROM categories WHERE id = {$product['category_id']}";
                                        $catResult = mysqli_query($conn, $catQuery);
                                        $category = mysqli_fetch_assoc($catResult)['name'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                                    <span><?php echo $product['name']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $category; ?></td>
                                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <span class="stock-badge <?php echo ($product['stock_quantity'] <= 5) ? 'stock-critical' : 'stock-low'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-sm">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No low stock products found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Sales (₹)',
                    data: <?php echo json_encode($sales); ?>,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
