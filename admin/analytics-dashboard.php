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

// Get sales data for the period
$salesQuery = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as total_sales,
                SUM(discount_amount) as total_discounts,
                payment_method
              FROM orders 
              WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
              GROUP BY DATE(created_at), payment_method
              ORDER BY date";
$salesResult = mysqli_query($conn, $salesQuery);
$salesData = mysqli_fetch_all($salesResult, MYSQLI_ASSOC);

// Process sales data for charts
$dates = [];
$totalSales = [];
$orderCounts = [];
$codSales = [];
$onlineSales = [];

$currentDate = new DateTime($startDate);
$endDateTime = new DateTime($endDate);
$endDateTime->modify('+1 day'); // Include end date

// Initialize arrays with all dates in range
while($currentDate < $endDateTime) {
    $dateStr = $currentDate->format('Y-m-d');
    $dateLabel = $currentDate->format('d M');
    
    $dates[$dateStr] = $dateLabel;
    $totalSales[$dateStr] = 0;
    $orderCounts[$dateStr] = 0;
    $codSales[$dateStr] = 0;
    $onlineSales[$dateStr] = 0;
    
    $currentDate->modify('+1 day');
}

// Fill in actual data
foreach($salesData as $data) {
    $date = $data['date'];
    
    if(isset($totalSales[$date])) {
        $totalSales[$date] += $data['total_sales'];
        $orderCounts[$date] += $data['order_count'];
        
        if($data['payment_method'] == 'cod') {
            $codSales[$date] += $data['total_sales'];
        } else {
            $onlineSales[$date] += $data['total_sales'];
        }
    }
}

// Get top selling products
$topProductsQuery = "SELECT 
                    p.id,
                    p.name,
                    p.image,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_sales,
                    COUNT(DISTINCT o.id) as order_count
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                  GROUP BY p.id
                  ORDER BY total_sales DESC
                  LIMIT 10";
$topProductsResult = mysqli_query($conn, $topProductsQuery);
$topProducts = mysqli_fetch_all($topProductsResult, MYSQLI_ASSOC);

// Get top categories
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

// Get customer acquisition data
$customerAcquisitionQuery = "SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as new_users
                          FROM users
                          WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                          GROUP BY DATE(created_at)
                          ORDER BY date";
$customerAcquisitionResult = mysqli_query($conn, $customerAcquisitionQuery);
$customerAcquisitionData = mysqli_fetch_all($customerAcquisitionResult, MYSQLI_ASSOC);

// Process customer acquisition data
$newUsers = [];
foreach($dates as $date => $label) {
    $newUsers[$date] = 0;
}

foreach($customerAcquisitionData as $data) {
    $date = $data['date'];
    if(isset($newUsers[$date])) {
        $newUsers[$date] = $data['new_users'];
    }
}

// Get conversion rate data
$visitsQuery = "SELECT 
                DATE(visit_date) as date,
                SUM(visit_count) as total_visits
              FROM site_visits
              WHERE visit_date BETWEEN '$startDate' AND '$endDate'
              GROUP BY DATE(visit_date)
              ORDER BY date";
$visitsResult = mysqli_query($conn, $visitsQuery);
$visitsData = [];

if($visitsResult) {
    $visitsData = mysqli_fetch_all($visitsResult, MYSQLI_ASSOC);
}

// Process conversion rate data
$visits = [];
$conversionRates = [];

foreach($dates as $date => $label) {
    $visits[$date] = 0;
    $conversionRates[$date] = 0;
}

foreach($visitsData as $data) {
    $date = $data['date'];
    if(isset($visits[$date])) {
        $visits[$date] = $data['total_visits'];
        
        // Calculate conversion rate if there were visits
        if($visits[$date] > 0 && isset($orderCounts[$date])) {
            $conversionRates[$date] = ($orderCounts[$date] / $visits[$date]) * 100;
        }
    }
}

// Calculate summary statistics
$totalSalesSum = array_sum($totalSales);
$totalOrdersSum = array_sum($orderCounts);
$totalNewUsers = array_sum($newUsers);

// Calculate average order value
$avgOrderValue = $totalOrdersSum > 0 ? $totalSalesSum / $totalOrdersSum : 0;

// Calculate average conversion rate
$totalVisits = array_sum($visits);
$avgConversionRate = $totalVisits > 0 ? ($totalOrdersSum / $totalVisits) * 100 : 0;

// Prepare chart data
$chartDates = array_values($dates);
$chartSales = array_values($totalSales);
$chartOrders = array_values($orderCounts);
$chartCodSales = array_values($codSales);
$chartOnlineSales = array_values($onlineSales);
$chartNewUsers = array_values($newUsers);
$chartConversionRates = array_values($conversionRates);

// Get category distribution for pie chart
$categoryDistributionQuery = "SELECT 
                             c.name,
                             SUM(oi.quantity * oi.price) as total_sales
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           JOIN categories c ON p.category_id = c.id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                           GROUP BY c.id
                           ORDER BY total_sales DESC";
$categoryDistributionResult = mysqli_query($conn, $categoryDistributionQuery);
$categoryDistribution = mysqli_fetch_all($categoryDistributionResult, MYSQLI_ASSOC);

// Process category distribution data
$categoryLabels = [];
$categorySales = [];

foreach($categoryDistribution as $category) {
    $categoryLabels[] = $category['name'];
    $categorySales[] = $category['total_sales'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .metric-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .metric-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .metric-cards {
                grid-template-columns: 1fr;
            }
        }
        
        .metric-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .metric-card h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .metric-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .metric-card .trend {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .metric-card .trend.positive {
            color: #2ecc71;
        }
        
        .metric-card .trend.negative {
            color: #e74c3c;
        }
        
        .date-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
        }
        
        .date-filter-form .form-group {
            margin-bottom: 0;
        }
        
        .quick-date-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .quick-filter {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
            text-decoration: none;
        }
        
        .quick-filter:hover {
            background-color: #e0e0e0;
        }
        
        .top-products-table img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
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
            
            <!-- Analytics Dashboard Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Analytics Dashboard</h1>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card">
                    <div class="card-body">
                        <form action="" method="GET" class="date-filter-form">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                            </div>
                            
                            <div class="form-group">
                                <div class="quick-date-filters">
                                    <a href="?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">Last 7 Days</a>
                                    <a href="?start_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">Last 30 Days</a>
                                    <a href="?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="quick-filter">This Month</a>
                                    <a href="?start_date=<?php echo date('Y-m-01', strtotime('-1 month')); ?>&end_date=<?php echo date('Y-m-t', strtotime('-1 month')); ?>" class="quick-filter">Last Month</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div class="metric-cards">
                    <div class="metric-card">
                        <h3>Total Sales</h3>
                        <div class="value">₹<?php echo number_format($totalSalesSum, 2); ?></div>
                        <div class="trend">
                            <?php
                            // Calculate previous period for comparison
                            $prevStartDate = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate)) / 86400 . ' days'));
                            $prevEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
                            
                            $prevSalesQuery = "SELECT SUM(total_amount) as total_sales FROM orders WHERE created_at BETWEEN '$prevStartDate 00:00:00' AND '$prevEndDate 23:59:59'";
                            $prevSalesResult = mysqli_query($conn, $prevSalesQuery);
                            $prevSales = mysqli_fetch_assoc($prevSalesResult)['total_sales'] ?: 0;
                            
                            $salesChange = $prevSales > 0 ? (($totalSalesSum - $prevSales) / $prevSales) * 100 : 0;
                            $trendClass = $salesChange >= 0 ? 'positive' : 'negative';
                            $trendIcon = $salesChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            
                            echo '<span class="trend ' . $trendClass . '"><i class="fas ' . $trendIcon . '"></i> ' . abs(round($salesChange, 1)) . '% from previous period</span>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <h3>Total Orders</h3>
                        <div class="value"><?php echo $totalOrdersSum; ?></div>
                        <div class="trend">
                            <?php
                            $prevOrdersQuery = "SELECT COUNT(*) as total_orders FROM orders WHERE created_at BETWEEN '$prevStartDate 00:00:00' AND '$prevEndDate 23:59:59'";
                            $prevOrdersResult = mysqli_query($conn, $prevOrdersQuery);
                            $prevOrders = mysqli_fetch_assoc($prevOrdersResult)['total_orders'] ?: 0;
                            
                            $ordersChange = $prevOrders > 0 ? (($totalOrdersSum - $prevOrders) / $prevOrders) * 100 : 0;
                            $trendClass = $ordersChange >= 0 ? 'positive' : 'negative';
                            $trendIcon = $ordersChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            
                            echo '<span class="trend ' . $trendClass . '"><i class="fas ' . $trendIcon . '"></i> ' . abs(round($ordersChange, 1)) . '% from previous period</span>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <h3>Average Order Value</h3>
                        <div class="value">₹<?php echo number_format($avgOrderValue, 2); ?></div>
                        <div class="trend">
                            <?php
                            $prevAOV = $prevOrders > 0 ? $prevSales / $prevOrders : 0;
                            $aovChange = $prevAOV > 0 ? (($avgOrderValue - $prevAOV) / $prevAOV) * 100 : 0;
                            $trendClass = $aovChange >= 0 ? 'positive' : 'negative';
                            $trendIcon = $aovChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            
                            echo '<span class="trend ' . $trendClass . '"><i class="fas ' . $trendIcon . '"></i> ' . abs(round($aovChange, 1)) . '% from previous period</span>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <h3>Conversion Rate</h3>
                        <div class="value"><?php echo number_format($avgConversionRate, 2); ?>%</div>
                        <div class="trend">
                            <?php
                            $prevVisitsQuery = "SELECT SUM(visit_count) as total_visits FROM site_visits WHERE visit_date BETWEEN '$prevStartDate' AND '$prevEndDate'";
                            $prevVisitsResult = mysqli_query($conn, $prevVisitsQuery);
                            $prevVisits = 0;
                            
                            if($prevVisitsResult) {
                                $prevVisits = mysqli_fetch_assoc($prevVisitsResult)['total_visits'] ?: 0;
                            }
                            
                            $prevConversionRate = $prevVisits > 0 ? ($prevOrders / $prevVisits) * 100 : 0;
                            $conversionChange = $prevConversionRate > 0 ? (($avgConversionRate - $prevConversionRate) / $prevConversionRate) * 100 : 0;
                            $trendClass = $conversionChange >= 0 ? 'positive' : 'negative';
                            $trendIcon = $conversionChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            
                            echo '<span class="trend ' . $trendClass . '"><i class="fas ' . $trendIcon . '"></i> ' . abs(round($conversionChange, 1)) . '% from previous period</span>';
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="card">
                    <div class="card-header">
                        <h2>Sales & Orders Trend</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods & Category Distribution -->
                <div class="analytics-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Payment Methods</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Category Distribution</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Acquisition & Conversion Rate -->
                <div class="analytics-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Customer Acquisition</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="customerAcquisitionChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Conversion Rate</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="conversionRateChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Products -->
                <div class="card">
                    <div class="card-header">
                        <h2>Top Selling Products</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table top-products-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Orders</th>
                                        <th>Quantity</th>
                                        <th>Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($topProducts as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                                    <span><?php echo $product['name']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $product['order_count']; ?></td>
                                            <td><?php echo $product['total_quantity']; ?></td>
                                            <td>₹<?php echo number_format($product['total_sales'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales & Orders Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartDates); ?>,
                    datasets: [
                        {
                            label: 'Sales (₹)',
                            data: <?php echo json_encode($chartSales); ?>,
                            backgroundColor: 'rgba(52, 152, 219, 0.2)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: <?php echo json_encode($chartOrders); ?>,
                            backgroundColor: 'rgba(46, 204, 113, 0.2)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
            
            // Payment Methods Chart
            const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
            const paymentChart = new Chart(paymentCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartDates); ?>,
                    datasets: [
                        {
                            label: 'COD Sales',
                            data: <?php echo json_encode($chartCodSales); ?>,
                            backgroundColor: 'rgba(231, 76, 60, 0.2)',
                            borderColor: 'rgba(231, 76, 60, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: 'Online Sales',
                            data: <?php echo json_encode($chartOnlineSales); ?>,
                            backgroundColor: 'rgba(155, 89, 182, 0.2)',
                            borderColor: 'rgba(155, 89, 182, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sales (₹)'
                            }
                        }
                    }
                }
            });
            
            // Category Distribution Chart
            const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categoryLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($categorySales); ?>,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(52, 73, 94, 0.7)',
                            'rgba(26, 188, 156, 0.7)',
                            'rgba(230, 126, 34, 0.7)',
                            'rgba(149, 165, 166, 0.7)',
                            'rgba(211, 84, 0, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Customer Acquisition Chart
            const customerCtx = document.getElementById('customerAcquisitionChart').getContext('2d');
            const customerChart = new Chart(customerCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartDates); ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?php echo json_encode($chartNewUsers); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'New Users'
                            }
                        }
                    }
                }
            });
            
            // Conversion Rate Chart
            const conversionCtx = document.getElementById('conversionRateChart').getContext('2d');
            const conversionChart = new Chart(conversionCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartDates); ?>,
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: <?php echo json_encode($chartConversionRates); ?>,
                        backgroundColor: 'rgba(46, 204, 113, 0.2)',
                        borderColor: 'rgba(46, 204, 113, 1)',
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
                            title: {
                                display: true,
                                text: 'Conversion Rate (%)'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
