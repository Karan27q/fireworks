<?php
session_start();
include 'includes/db_connect.php';
include 'includes/order_functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get cart items
$cartQuery = "SELECT c.*, p.name, p.price, p.stock_quantity, p.image 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = $userId";
$cartResult = mysqli_query($conn, $cartQuery);

if(mysqli_num_rows($cartResult) === 0) {
    // Cart is empty
    header('Location: cart.php?error=empty');
    exit;
}

$cartItems = mysqli_fetch_all($cartResult, MYSQLI_ASSOC);

// Calculate order totals
$subtotal = 0;
$items = [];

foreach($cartItems as $item) {
    // Check if product is in stock
    if($item['stock_quantity'] < $item['quantity']) {
        header('Location: cart.php?error=stock&product=' . $item['product_id']);
        exit;
    }
    
    $itemTotal = $item['price'] * $item['quantity'];
    $subtotal += $itemTotal;
    
    $items[] = [
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
    ];
}

// Get site settings
$settingsQuery = "SELECT * FROM site_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);

// Check minimum order amount
if($subtotal < $settings['min_order_amount']) {
    header('Location: cart.php?error=min_order&min=' . $settings['min_order_amount']);
    exit;
}

// Get form data
$shippingAddress = mysqli_real_escape_string($conn, $_POST['address']);
$shippingCity = mysqli_real_escape_string($conn, $_POST['city']);
$shippingState = mysqli_real_escape_string($conn, $_POST['state']);
$shippingPincode = mysqli_real_escape_string($conn, $_POST['pincode']);
$shippingPhone = mysqli_real_escape_string($conn, $_POST['phone']);
$paymentMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);
$notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;

// Apply discount if code provided
$discountId = null;
$discountAmount = 0;

if(isset($_POST['discount_code']) && !empty($_POST['discount_code'])) {
    $discountCode = mysqli_real_escape_string($conn, $_POST['discount_code']);
    
    // Check if discount code is valid
    $discountQuery = "SELECT * FROM discounts 
                     WHERE code = '$discountCode' 
                     AND active = 1 
                     AND start_date <= CURDATE() 
                     AND end_date >= CURDATE()";
    $discountResult = mysqli_query($conn, $discountQuery);
    
    if(mysqli_num_rows($discountResult) === 1) {
        $discount = mysqli_fetch_assoc($discountResult);
        
        // Check if discount has reached usage limit
        if($discount['usage_limit'] > 0 && $discount['usage_count'] >= $discount['usage_limit']) {
            header('Location: checkout.php?error=discount_limit');
            exit;
        }
        
        // Check if user has reached their usage limit
        $userDiscountQuery = "SELECT usage_count FROM customer_discounts 
                             WHERE user_id = $userId AND discount_id = {$discount['id']}";
        $userDiscountResult = mysqli_query($conn, $userDiscountQuery);
        
        if(mysqli_num_rows($userDiscountResult) === 1) {
            $userDiscount = mysqli_fetch_assoc($userDiscountResult);
            
            if($userDiscount['usage_count'] >= $discount['user_usage_limit']) {
                header('Location: checkout.php?error=user_discount_limit');
                exit;
            }
        }
        
        // Check minimum order amount
        if($discount['min_order_amount'] > 0 && $subtotal < $discount['min_order_amount']) {
            header('Location: checkout.php?error=discount_min_order&min=' . $discount['min_order_amount']);
            exit;
        }
        
        // Calculate discount amount
        if($discount['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ($discount['discount_value'] / 100);
            
            // Apply maximum discount if set
            if($discount['max_discount_amount'] > 0 && $discountAmount > $discount['max_discount_amount']) {
                $discountAmount = $discount['max_discount_amount'];
            }
        } else {
            $discountAmount = $discount['discount_value'];
            
            // Make sure discount doesn't exceed subtotal
            if($discountAmount > $subtotal) {
                $discountAmount = $subtotal;
            }
        }
        
        $discountId = $discount['id'];
    } else {
        header('Location: checkout.php?error=invalid_discount');
        exit;
    }
}

// Calculate shipping and tax
$shippingAmount = $settings['shipping_fee'];
$taxableAmount = $subtotal - $discountAmount;
$taxAmount = $taxableAmount * ($settings['tax_rate'] / 100);
$totalAmount = $taxableAmount + $shippingAmount + $taxAmount;

// Prepare order data
$orderData = [
    'subtotal' => $subtotal,
    'discount_id' => $discountId,
    'discount_amount' => $discountAmount,
    'shipping_amount' => $shippingAmount,
    'tax_amount' => $taxAmount,
    'total_amount' => $totalAmount,
    'shipping_address' => $shippingAddress,
    'shipping_city' => $shippingCity,
    'shipping_state' => $shippingState,
    'shipping_pincode' => $shippingPincode,
    'shipping_phone' => $shippingPhone,
    'payment_method' => $paymentMethod,
    'payment_status' => $paymentMethod === 'cod' ? 'pending' : 'pending',
    'notes' => $notes
];

// Create order
$orderId = createOrder($userId, $orderData, $items);

if(!$orderId) {
    header('Location: checkout.php?error=order_failed');
    exit;
}

// Handle payment based on method
if($paymentMethod === 'cod') {
    // Cash on Delivery - redirect to success page
    header('Location: order-success.php?id=' . $orderId);
    exit;
} else if($paymentMethod === 'online') {
    // Online payment - redirect to payment page
    header('Location: order-payment.php?id=' . $orderId);
    exit;
} else {
    // Invalid payment method
    header('Location: checkout.php?error=payment_method');
    exit;
}
