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

// Set default date range (last 30 days)
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

// Handle date range filter
if(isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
}

// Sales Summary
$salesQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as average_order_value,
                SUM(discount_amount) as total_discounts
              FROM orders 
              WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$salesResult = mysqli_query($conn, $salesQuery);
$salesSummary = mysqli_fetch_assoc($salesResult);

// Sales by Status
$statusQuery = "SELECT 
                status,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
              FROM orders 
              WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
              GROUP BY status";
$statusResult = mysqli_query($conn, $statusQuery);
$salesByStatus = mysqli_fetch_all($statusResult, MYSQLI_ASSOC);

// Sales by Payment Method
$paymentQuery = "SELECT 
                payment_method,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
              FROM orders 
              WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
              GROUP BY payment_method";
$paymentResult = mysqli_query($conn, $paymentQuery);
$salesByPayment = mysqli_fetch_all($paymentResult, MYSQLI_ASSOC);

// Top Products
$topProductsQuery = "SELECT 
                    p.id,
                    p.name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_sales
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                  GROUP BY p.id
                  ORDER BY total_sales DESC
                  LIMIT 10";
$topProductsResult = mysqli_query($conn, $topProductsQuery);
$topProducts = mysqli_fetch_all($topProductsResult, MYSQLI_ASSOC);

// Top Categories
$topCategoriesQuery = "SELECT 
                      c.id,
                      c.name,
                      COUNT(DISTINCT o.id) as order_count,
                      SUM(oi.quantity) as total_quantity,
                      SUM(oi.quantity * oi.price) as total_sales
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                    GROUP BY c.id
                    ORDER BY total_sales DESC
                    LIMIT 5";
$topCategoriesResult = mysqli_query($conn, $topCategoriesQuery);
$topCategories = mysqli_fetch_all($topCategoriesResult, MYSQLI_ASSOC);

// Daily Sales for Chart
$dailySalesQuery = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales
                  FROM orders
                  WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                  GROUP BY DATE(created_at)
                  ORDER BY date";
$dailySalesResult = mysqli_query($conn, $dailySalesQuery);
$dailySales = mysqli_fetch_all($dailySalesResult, MYSQLI_ASSOC);

// Format data for chart
$dates = [];
$sales = [];
$orders = [];

foreach($dailySales as $day) {
    $dates[] = date('d M', strtotime($day['date']));
    $sales[] = $day['total_sales'];
    $orders[] = $day['order_count'];
}

$chartDates = json_encode($dates);
$chartSales = json_encode($sales);
$chartOrders = json_encode($orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Admin Panel</title>
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
            
            <!-- Reports Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Sales Reports</h1>
                    <div class="report-actions">
                        <a href="export-report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> Export Report
                        </a>
                    </div>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card">
                    <div class="card-body">
                        <form action="" method="GET" class="date-filter-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                                </div>
                                
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="quick-date-filters">
                                        <a href="?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">Last 7 Days</a>
                                        <a href="?start_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">Last 30 Days</a>
                                        <a href="?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">This Month</a>
                                        <a href="?start_date=<?php echo date('Y-m-01', strtotime('-1 month')); ?>&end_date=<?php echo date('Y-m-t', strtotime('-1 month')); ?>" class="quick-filter">Last Month</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sales Summary -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Orders</h3>
                            <p><?php echo $salesSummary['total_orders']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Sales</h3>
                            <p>₹<?php echo number_format($salesSummary['total_sales'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Average Order Value</h3>
                            <p>₹<?php echo number_format($salesSummary['average_order_value'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3>Total Discounts</h3>
                            <p>₹<?php echo number_format($salesSummary['total_discounts'], 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="card">
                    <div class="card-header">
                        <h2>Sales Trend</h2>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
                
                <!-- Sales by Status and Payment Method -->
                <div class="report-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Sales by Status</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Orders</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($salesByStatus as $status): ?>
                                            <tr>
                                                <td>
                                                    <span class="status-badge status-<?php echo $status['status']; ?>">
                                                        <?php echo ucfirst($status['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $status['order_count']; ?></td>
                                                <td>₹<?php echo number_format($status['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Sales by Payment Method</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Orders</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($salesByPayment as $payment): ?>
                                            <tr>
                                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                <td><?php echo $payment['order_count']; ?></td>
                                                <td>₹<?php echo number_format($payment['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Products and Categories -->
                <div class="report-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Top Products</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($topProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>">
                                                        <?php echo $product['name']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $product['total_quantity']; ?></td>
                                                <td>₹<?php echo number_format($product['total_sales'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Top Categories</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Orders</th>
                                            <th>Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($topCategories as $category): ?>
                                            <tr>
                                                <td>
                                                    <a href="edit-category.php?id=<?php echo $category['id']; ?>">
                                                        <?php echo $category['name']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $category['order_count']; ?></td>
                                                <td>₹<?php echo number_format($category['total_sales'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chartDates; ?>,
                    datasets: [
                        {
                            label: 'Sales (₹)',
                            data: <?php echo $chartSales; ?>,
                            backgroundColor: 'rgba(76, 175, 80, 0.2)',
                            borderColor: 'rgba(76, 175, 80, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: <?php echo $chartOrders; ?>,
                            backgroundColor: 'rgba(33, 150, 243, 0.2)',
                            borderColor: 'rgba(33, 150, 243, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Sales (₹)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'Orders'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
