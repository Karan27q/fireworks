<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

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
$itemsQuery = "SELECT oi.*, p.name as product_name, p.sku as product_sku 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);
$orderItems = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

// Get site settings
$settingsQuery = "SELECT * FROM site_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);

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
    <title>Invoice #<?php echo $orderId; ?> - <?php echo $settings['site_name']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-info h1 {
            margin: 0 0 5px 0;
            color: #4caf50;
        }
        
        .company-info p {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-details h2 {
            margin: 0 0 5px 0;
            color: #4caf50;
        }
        
        .invoice-details p {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .billing-info, .shipping-info {
            flex: 1;
        }
        
        .info-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #4caf50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-content p {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .totals-row.total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 5px;
        }
        
        .notes {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .notes h3 {
            margin: 0 0 10px 0;
            color: #4caf50;
        }
        
        .notes p {
            font-size: 14px;
            margin: 0;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #777;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .invoice-container {
                border: none;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="print-button" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print();" style="padding: 8px 16px; background-color: #4caf50; color: white; border: none; cursor: pointer;">Print Invoice</button>
        </div>
        
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1><?php echo $settings['site_name']; ?></h1>
                <p><?php echo $settings['address']; ?></p>
                <p><?php echo $settings['location']; ?></p>
                <p>Phone: <?php echo $settings['phone']; ?></p>
                <p>Email: <?php echo $settings['email']; ?></p>
                <?php if($settings['license_text']): ?>
                <p><?php echo $settings['license_text']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> INV-<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Order #:</strong> <?php echo $orderId; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="customer-info">
            <div class="billing-info">
                <div class="info-title">BILL TO</div>
                <div class="info-content">
                    <p><?php echo $order['customer_name']; ?></p>
                    <p>Email: <?php echo $order['customer_email']; ?></p>
                    <p>Phone: <?php echo $order['customer_phone']; ?></p>
                </div>
            </div>
            
            <div class="shipping-info">
                <div class="info-title">SHIP TO</div>
                <div class="info-content">
                    <p><?php echo $order['customer_name']; ?></p>
                    <p><?php echo $order['shipping_address']; ?></p>
                    <p><?php echo $order['shipping_city']; ?>, <?php echo $order['shipping_state']; ?> - <?php echo $order['shipping_pincode']; ?></p>
                    <p>Phone: <?php echo $order['shipping_phone']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orderItems as $item): ?>
                    <tr>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['product_sku']; ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="text-right">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Order Totals -->
        <div class="totals">
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
                <span>Tax (<?php echo $settings['tax_rate']; ?>%):</span>
                <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            
            <div class="totals-row total">
                <span>Total:</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="notes">
            <h3>Notes</h3>
            <?php if($order['notes']): ?>
                <p><?php echo nl2br($order['notes']); ?></p>
            <?php else: ?>
                <p>Thank you for your business! All products are non-returnable and non-refundable.</p>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated invoice and does not require a signature.</p>
        </div>
    </div>
</body>
</html>
