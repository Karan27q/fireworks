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

// Check if order ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = $_GET['id'];

// Get order details
$orderQuery = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = $orderId";
$orderResult = mysqli_query($conn, $orderQuery);

if(mysqli_num_rows($orderResult) === 0) {
    header('Location: orders.php');
    exit;
}

$order = mysqli_fetch_assoc($orderResult);

// Get order items
$itemsQuery = "SELECT oi.*, p.name as product_name, p.image as product_image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);
$orderItems = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

// Handle status update
if(isset($_POST['update_status'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    $trackingNumber = isset($_POST['tracking_number']) ? mysqli_real_escape_string($conn, $_POST['tracking_number']) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    
    $updateQuery = "UPDATE orders SET 
                    status = '$newStatus', 
                    tracking_number = '$trackingNumber', 
                    notes = '$notes', 
                    updated_at = NOW() 
                    WHERE id = $orderId";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Order status updated successfully";
        
        // Refresh order data
        $orderResult = mysqli_query($conn, $orderQuery);
        $order = mysqli_fetch_assoc($orderResult);
    } else {
        $errorMessage = "Failed to update order status";
    }
}

// Get discount information if applied
$discountInfo = null;
if($order['discount_id']) {
    $discountQuery = "SELECT * FROM discounts WHERE id = {$order['discount_id']}";
    $discountResult = mysqli_query($conn, $discountQuery);
    $discountInfo = mysqli_fetch_assoc($discountResult);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Admin Panel</title>
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
            
            <!-- Order Details Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Order #<?php echo $orderId; ?></h1>
                    <div class="header-actions">
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                        <a href="print-invoice.php?id=<?php echo $orderId; ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-print"></i> Print Invoice
                        </a>
                    </div>
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
                
                <div class="order-details-container">
                    <div class="order-details-grid">
                        <!-- Order Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Order Summary</h2>
                            </div>
                            <div class="card-body">
                                <div class="order-info">
                                    <div class="info-group">
                                        <span class="info-label">Order Date:</span>
                                        <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value">
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Payment Method:</span>
                                        <span class="info-value"><?php echo ucfirst($order['payment_method']); ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Payment Status:</span>
                                        <span class="info-value">
                                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <?php if($order['tracking_number']): ?>
                                    <div class="info-group">
                                        <span class="info-label">Tracking Number:</span>
                                        <span class="info-value"><?php echo $order['tracking_number']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Customer Information</h2>
                            </div>
                            <div class="card-body">
                                <div class="customer-info">
                                    <div class="info-group">
                                        <span class="info-label">Name:</span>
                                        <span class="info-value"><?php echo $order['customer_name']; ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?php echo $order['customer_email']; ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value"><?php echo $order['customer_phone']; ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <span class="info-label">Shipping Address:</span>
                                        <span class="info-value">
                                            <?php echo $order['shipping_address']; ?><br>
                                            <?php echo $order['shipping_city']; ?>, <?php echo $order['shipping_state']; ?><br>
                                            <?php echo $order['shipping_pincode']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Order Items</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-info">
                                                        <img src="../uploads/products/<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>">
                                                        <span><?php echo $item['product_name']; ?></span>
                                                    </div>
                                                </td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="order-totals">
                                <div class="totals-row">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                
                                <?php if($discountInfo): ?>
                                <div class="totals-row">
                                    <span>Discount (<?php echo $discountInfo['code']; ?>):</span>
                                    <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="totals-row">
                                    <span>Shipping:</span>
                                    <span>₹<?php echo number_format($order['shipping_amount'], 2); ?></span>
                                </div>
                                
                                <div class="totals-row">
                                    <span>Tax:</span>
                                    <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                                </div>
                                
                                <div class="totals-row total">
                                    <span>Total:</span>
                                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Update Order Status -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Update Order Status</h2>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="pending" <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($order['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo ($order['status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo ($order['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-md-6">
                                        <label for="tracking_number">Tracking Number</label>
                                        <input type="text" id="tracking_number" name="tracking_number" class="form-control" value="<?php echo $order['tracking_number']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo $order['notes']; ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
