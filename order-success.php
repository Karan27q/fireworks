<?php
// Set page title
$pageTitle = "Order Confirmation";

// Include database connection
include 'includes/db_connect.php';

// Check if order ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$orderId = $_GET['id'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get order details
$orderQuery = "SELECT * FROM orders WHERE id = $orderId";
if($userId > 0) {
    $orderQuery .= " AND user_id = $userId";
}

$orderResult = mysqli_query($conn, $orderQuery);

// Check if order exists
if(mysqli_num_rows($orderResult) === 0) {
    header('Location: index.php');
    exit;
}

$order = mysqli_fetch_assoc($orderResult);

// Get user details
$userQuery = "SELECT * FROM users WHERE id = {$order['user_id']}";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

// Get order items
$itemsQuery = "SELECT oi.*, p.name as product_name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);
$orderItems = [];

while($item = mysqli_fetch_assoc($itemsResult)) {
    $orderItems[] = $item;
}

// Check if payment was just confirmed
$paymentConfirmed = isset($_GET['payment']) && $_GET['payment'] === 'confirmed';

// Include header
include 'includes/header.php';

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}
?>

<div class="container">
    <div class="order-success-container <?php echo $isMobile ? 'mobile-view' : ''; ?>">
        <?php if($paymentConfirmed): ?>
        <div class="success-header payment-confirmed">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Payment Confirmation Received!</h1>
            <p>Thank you for confirming your payment. We will verify your payment and process your order soon.</p>
        </div>
        <?php else: ?>
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. Your order has been received and is being processed.</p>
        </div>
        <?php endif; ?>
        
        <div class="order-details-card">
            <div class="card-header">
                <h2>Order Details</h2>
                <span class="order-id">Order #<?php echo $orderId; ?></span>
            </div>
            
            <div class="card-body">
                <div class="order-info">
                    <div class="info-column">
                        <h3>Order Information</h3>
                        <p><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></p>
                        <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                        <p><strong>Payment Status:</strong> <span class="status-badge <?php echo $order['payment_status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['payment_status'])); ?></span></p>
                    </div>
                    
                    <div class="info-column">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo $user['name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        <p><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
                    </div>
                    
                    <div class="info-column">
                        <h3>Shipping Address</h3>
                        <p><?php echo $order['shipping_address']; ?></p>
                        <p><?php echo $order['shipping_city']; ?>, <?php echo $order['shipping_state']; ?> - <?php echo $order['shipping_pincode']; ?></p>
                        <p><strong>Phone:</strong> <?php echo $order['shipping_phone']; ?></p>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    <table class="items-table">
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
                                <td class="product-col">
                                    <div class="product-info">
                                        <img src="uploads/products/<?php echo $item['image']; ?>" alt="<?php echo $item['product_name']; ?>">
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
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    
                    <?php if($order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>Discount</span>
                        <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($order['shipping_amount'], 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($order['payment_method'] === 'online' && $order['payment_status'] === 'pending'): ?>
        <div class="payment-action-card">
            <h3>Complete Your Payment</h3>
            <p>Your order has been placed, but payment is still pending. Please complete your payment to process your order.</p>
            <a href="order-payment.php?id=<?php echo $orderId; ?>" class="btn btn-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Make Payment</a>
        </div>
        <?php endif; ?>
        
        <div class="next-steps">
            <h3>What's Next?</h3>
            <div class="steps-container">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h4>Order Confirmation</h4>
                    <p>We've received your order and will begin processing it soon.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h4>Order Processing</h4>
                    <p>Your order will be prepared and packed for shipping.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h4>Shipping</h4>
                    <p>Your order will be shipped to your address. You'll receive updates via email.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h4>Delivery</h4>
                    <p>Your order will be delivered to your doorstep.</p>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="my-orders.php" class="btn btn-outline-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">View All Orders</a>
            <a href="index.php" class="btn btn-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Continue Shopping</a>
        </div>
    </div>
</div>

<style>
    .order-success-container {
        margin: 40px 0;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .success-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .success-header.payment-confirmed {
        background-color: #d4edda;
        padding: 20px;
        border-radius: 8px;
        border-left: 5px solid #28a745;
    }
    
    .success-icon {
        font-size: 60px;
        color: #28a745;
        margin-bottom: 20px;
    }
    
    .success-header h1 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .success-header p {
        font-size: 18px;
        color: #555;
    }
    
    .order-details-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .card-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-header h2 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    
    .order-id {
        font-weight: bold;
        color: #555;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .order-info {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }
    
    .info-column {
        flex: 1;
        min-width: 250px;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .info-column h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .info-column p {
        margin-bottom: 8px;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: bold;
    }
    
    .status-badge.pending {
        background-color: #ffeeba;
        color: #856404;
    }
    
    .status-badge.processing {
        background-color: #b8daff;
        color: #004085;
    }
    
    .status-badge.completed {
        background-color: #c3e6cb;
        color: #155724;
    }
    
    .status-badge.cancelled {
        background-color: #f5c6cb;
        color: #721c24;
    }
    
    .status-badge.pending_verification {
        background-color: #d6d8db;
        color: #383d41;
    }
    
    .order-items {
        margin-bottom: 30px;
    }
    
    .order-items h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
        color: #333;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .items-table th,
    .items-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .items-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    
    .product-info {
        display: flex;
        align-items: center;
    }
    
    .product-info img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        margin-right: 10px;
        border-radius: 4px;
    }
    
    .order-summary {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        max-width: 400px;
        margin-left: auto;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .summary-row.total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        font-weight: bold;
        font-size: 18px;
    }
    
    .payment-action-card {
        background-color: #fff3cd;
        border-left: 5px solid #ffc107;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .payment-action-card h3 {
        margin-top: 0;
        color: #856404;
    }
    
    .next-steps {
        margin-bottom: 30px;
    }
    
    .next-steps h3 {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .steps-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    
    .step {
        flex: 1;
        min-width: 200px;
        text-align: center;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin: 0 10px 20px;
    }
    
    .step-icon {
        font-size: 30px;
        color: #007bff;
        margin-bottom: 15px;
    }
    
    .step h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
    }
    
    .step p {
        margin: 0;
        color: #555;
    }
    
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: #fff;
        border: 2px solid #007bff;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0069d9;
    }
    
    .btn-outline-primary {
        background-color: transparent;
        color: #007bff;
        border: 2px solid #007bff;
    }
    
    .btn-outline-primary:hover {
        background-color: #007bff;
        color: #fff;
    }
    
    /* Mobile Styles */
    .mobile-view .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mobile-view .order-id {
        margin-top: 5px;
    }
    
    .mobile-view .order-info {
        flex-direction: column;
    }
    
    .mobile-view .info-column {
        width: 100%;
        padding: 0;
    }
    
    .mobile-view .items-table {
        display: block;
        overflow-x: auto;
    }
    
    .mobile-view .order-summary {
        max-width: 100%;
    }
    
    .mobile-view .steps-container {
        flex-direction: column;
    }
    
    .mobile-view .step {
        width: 100%;
        margin: 0 0 15px;
    }
    
    .mobile-view .action-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .mobile-btn {
        width: 100%;
        text-align: center;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
