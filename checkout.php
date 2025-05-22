<?php
// Set page title
$pageTitle = "Checkout";

// Include database connection
include 'includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user details
$userQuery = "SELECT * FROM users WHERE id = $userId";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

// Get cart items
$cartQuery = "SELECT c.*, p.name, p.price, p.stock_quantity, p.image 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = $userId";
$cartResult = mysqli_query($conn, $cartQuery);

// Check if cart is empty
if(mysqli_num_rows($cartResult) === 0) {
    header('Location: cart.php?error=empty');
    exit;
}

$cartItems = mysqli_fetch_all($cartResult, MYSQLI_ASSOC);

// Calculate order totals
$subtotal = 0;
foreach($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Get site settings
$settingsQuery = "SELECT * FROM site_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);

// Calculate shipping and tax
$shippingAmount = $settings['shipping_fee'];
$taxAmount = $subtotal * ($settings['tax_rate'] / 100);
$totalAmount = $subtotal + $shippingAmount + $taxAmount;

// Get payment methods
$paymentQuery = "SELECT * FROM payment_details WHERE active = 1 ORDER BY display_order ASC";
$paymentResult = mysqli_query($conn, $paymentQuery);

// Include header
include 'includes/header.php';

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}
?>

<div class="container">
    <div class="page-header">
        <h1>Checkout</h1>
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="cart.php">Cart</a> &gt; 
            <span>Checkout</span>
        </div>
    </div>
    
    <div class="checkout-container <?php echo $isMobile ? 'mobile-view' : ''; ?>">
        <div class="checkout-form-container">
            <form action="checkout-process.php" method="POST" id="checkout-form">
                <div class="checkout-section">
                    <h2>Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $user['name']; ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $user['email']; ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" rows="3" required><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $user['city']; ?>" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="state">State</label>
                            <select id="state" name="state" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" required>
                                <option value="">Select State</option>
                                <option value="Andhra Pradesh" <?php echo $user['state'] === 'Andhra Pradesh' ? 'selected' : ''; ?>>Andhra Pradesh</option>
                                <option value="Arunachal Pradesh" <?php echo $user['state'] === 'Arunachal Pradesh' ? 'selected' : ''; ?>>Arunachal Pradesh</option>
                                <option value="Assam" <?php echo $user['state'] === 'Assam' ? 'selected' : ''; ?>>Assam</option>
                                <option value="Bihar" <?php echo $user['state'] === 'Bihar' ? 'selected' : ''; ?>>Bihar</option>
                                <option value="Chhattisgarh" <?php echo $user['state'] === 'Chhattisgarh' ? 'selected' : ''; ?>>Chhattisgarh</option>
                                <option value="Goa" <?php echo $user['state'] === 'Goa' ? 'selected' : ''; ?>>Goa</option>
                                <option value="Gujarat" <?php echo $user['state'] === 'Gujarat' ? 'selected' : ''; ?>>Gujarat</option>
                                <option value="Haryana" <?php echo $user['state'] === 'Haryana' ? 'selected' : ''; ?>>Haryana</option>
                                <option value="Himachal Pradesh" <?php echo $user['state'] === 'Himachal Pradesh' ? 'selected' : ''; ?>>Himachal Pradesh</option>
                                <option value="Jharkhand" <?php echo $user['state'] === 'Jharkhand' ? 'selected' : ''; ?>>Jharkhand</option>
                                <option value="Karnataka" <?php echo $user['state'] === 'Karnataka' ? 'selected' : ''; ?>>Karnataka</option>
                                <option value="Kerala" <?php echo $user['state'] === 'Kerala' ? 'selected' : ''; ?>>Kerala</option>
                                <option value="Madhya Pradesh" <?php echo $user['state'] === 'Madhya Pradesh' ? 'selected' : ''; ?>>Madhya Pradesh</option>
                                <option value="Maharashtra" <?php echo $user['state'] === 'Maharashtra' ? 'selected' : ''; ?>>Maharashtra</option>
                                <option value="Manipur" <?php echo $user['state'] === 'Manipur' ? 'selected' : ''; ?>>Manipur</option>
                                <option value="Meghalaya" <?php echo $user['state'] === 'Meghalaya' ? 'selected' : ''; ?>>Meghalaya</option>
                                <option value="Mizoram" <?php echo $user['state'] === 'Mizoram' ? 'selected' : ''; ?>>Mizoram</option>
                                <option value="Nagaland" <?php echo $user['state'] === 'Nagaland' ? 'selected' : ''; ?>>Nagaland</option>
                                <option value="Odisha" <?php echo $user['state'] === 'Odisha' ? 'selected' : ''; ?>>Odisha</option>
                                <option value="Punjab" <?php echo $user['state'] === 'Punjab' ? 'selected' : ''; ?>>Punjab</option>
                                <option value="Rajasthan" <?php echo $user['state'] === 'Rajasthan' ? 'selected' : ''; ?>>Rajasthan</option>
                                <option value="Sikkim" <?php echo $user['state'] === 'Sikkim' ? 'selected' : ''; ?>>Sikkim</option>
                                <option value="Tamil Nadu" <?php echo $user['state'] === 'Tamil Nadu' ? 'selected' : ''; ?>>Tamil Nadu</option>
                                <option value="Telangana" <?php echo $user['state'] === 'Telangana' ? 'selected' : ''; ?>>Telangana</option>
                                <option value="Tripura" <?php echo $user['state'] === 'Tripura' ? 'selected' : ''; ?>>Tripura</option>
                                <option value="Uttar Pradesh" <?php echo $user['state'] === 'Uttar Pradesh' ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                <option value="Uttarakhand" <?php echo $user['state'] === 'Uttarakhand' ? 'selected' : ''; ?>>Uttarakhand</option>
                                <option value="West Bengal" <?php echo $user['state'] === 'West Bengal' ? 'selected' : ''; ?>>West Bengal</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="pincode">Pincode</label>
                            <input type="text" id="pincode" name="pincode" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" value="<?php echo $user['pincode']; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-section">
                    <h2>Payment Method</h2>
                    
                    <div class="payment-methods">
                        <?php 
                        mysqli_data_seek($paymentResult, 0);
                        $firstPayment = true;
                        while($payment = mysqli_fetch_assoc($paymentResult)): 
                        ?>
                        <div class="payment-method">
                            <input type="radio" id="payment_<?php echo $payment['id']; ?>" name="payment_method" value="online" <?php echo $firstPayment ? 'checked' : ''; ?> required>
                            <label for="payment_<?php echo $payment['id']; ?>">
                                <span class="payment-name"><?php echo $payment['title']; ?></span>
                                <span class="payment-description"><?php echo $payment['short_description']; ?></span>
                            </label>
                        </div>
                        <?php 
                        $firstPayment = false;
                        endwhile; 
                        ?>
                    </div>
                    
                    <div class="payment-note">
                        <p>You will be able to complete your payment after placing the order.</p>
                    </div>
                </div>
                
                <div class="checkout-section">
                    <h2>Additional Information</h2>
                    
                    <div class="form-group">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>" rows="3" placeholder="Notes about your order, e.g. special notes for delivery"></textarea>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="order-summary-container">
            <div class="order-summary">
                <h2>Order Summary</h2>
                
                <div class="cart-items">
                    <?php foreach($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="item-image">
                            <img src="uploads/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                            <span class="item-quantity"><?php echo $item['quantity']; ?></span>
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo $item['name']; ?></h3>
                            <p class="item-price">₹<?php echo number_format($item['price'], 2); ?></p>
                        </div>
                        <div class="item-total">
                            ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="coupon-section">
                    <div class="coupon-form">
                        <input type="text" name="discount_code" id="discount_code" placeholder="Coupon Code" class="form-control <?php echo $isMobile ? 'mobile-input' : ''; ?>">
                        <button type="button" id="apply-coupon" class="btn btn-secondary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Apply</button>
                    </div>
                    <div id="coupon-message"></div>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="total-row" id="discount-row" style="display: none;">
                        <span>Discount</span>
                        <span id="discount-amount">-₹0.00</span>
                    </div>
                    
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($shippingAmount, 2); ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span>Tax (<?php echo $settings['tax_rate']; ?>%)</span>
                        <span>₹<?php echo number_format($taxAmount, 2); ?></span>
                    </div>
                    
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span id="total-amount">₹<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                </div>
                
                <div class="checkout-actions">
                    <button type="button" id="place-order-btn" class="btn btn-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Place Order</button>
                    <a href="cart.php" class="back-to-cart <?php echo $isMobile ? 'mobile-link' : ''; ?>">Back to Cart</a>
                </div>
            </div>
            
            <div class="secure-checkout">
                <div class="secure-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <p>Secure Checkout</p>
            </div>
        </div>
    </div>
</div>

<style>
    .checkout-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin: 30px 0;
    }
    
    .checkout-form-container {
        flex: 1;
        min-width: 500px;
    }
    
    .order-summary-container {
        width: 350px;
    }
    
    .checkout-section {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .checkout-section h2 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 20px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group.half {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    
    .form-control:focus {
        border-color: #007bff;
        outline: none;
    }
    
    .payment-methods {
        margin-bottom: 20px;
    }
    
    .payment-method {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .payment-method:hover {
        border-color: #007bff;
    }
    
    .payment-method input[type="radio"] {
        margin-top: 5px;
        margin-right: 10px;
    }
    
    .payment-method label {
        flex: 1;
        cursor: pointer;
    }
    
    .payment-name {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .payment-description {
        display: block;
        font-size: 14px;
        color: #666;
    }
    
    .payment-note {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        font-size: 14px;
        color: #555;
    }
    
    .order-summary {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .order-summary h2 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 20px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .cart-items {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 20px;
    }
    
    .cart-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .item-image {
        position: relative;
        width: 60px;
        height: 60px;
        margin-right: 15px;
    }
    
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .item-quantity {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #007bff;
        color: #fff;
        font-size: 12px;
        font-weight: bold;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-name {
        margin: 0 0 5px;
        font-size: 16px;
    }
    
    .item-price {
        margin: 0;
        color: #666;
    }
    
    .item-total {
        font-weight: bold;
    }
    
    .coupon-section {
        margin-bottom: 20px;
    }
    
    .coupon-form {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    .coupon-form input {
        flex: 1;
    }
    
    #coupon-message {
        font-size: 14px;
    }
    
    .order-totals {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .total-row.grand-total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        font-weight: bold;
        font-size: 18px;
    }
    
    .checkout-actions {
        text-align: center;
    }
    
    .back-to-cart {
        display: block;
        margin-top: 10px;
        color: #007bff;
        text-decoration: none;
    }
    
    .back-to-cart:hover {
        text-decoration: underline;
    }
    
    .secure-checkout {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .secure-icon {
        margin-right: 10px;
        color: #28a745;
    }
    
    .secure-checkout p {
        margin: 0;
        font-weight: 600;
        color: #333;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: #fff;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        color: #fff;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    /* Mobile Styles */
    .mobile-view {
        flex-direction: column;
    }
    
    .mobile-view .checkout-form-container,
    .mobile-view .order-summary-container {
        width: 100%;
        min-width: auto;
    }
    
    .mobile-view .form-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .mobile-view .form-group.half {
        width: 100%;
    }
    
    .mobile-input {
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    .mobile-btn {
        width: 100%;
        padding: 12px 20px;
    }
    
    .mobile-link {
        display: block;
        text-align: center;
        padding: 10px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Place order button
    const placeOrderBtn = document.getElementById('place-order-btn');
    const checkoutForm = document.getElementById('checkout-form');
    
    if(placeOrderBtn && checkoutForm) {
        placeOrderBtn.addEventListener('click', function() {
            // Validate form
            if(checkoutForm.checkValidity()) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Submit form
                checkoutForm.submit();
            } else {
                // Trigger HTML5 validation
                checkoutForm.reportValidity();
            }
        });
    }
    
    // Apply coupon button
    const applyCouponBtn = document.getElementById('apply-coupon');
    const discountCodeInput = document.getElementById('discount_code');
    const couponMessage = document.getElementById('coupon-message');
    const discountRow = document.getElementById('discount-row');
    const discountAmount = document.getElementById('discount-amount');
    const totalAmount = document.getElementById('total-amount');
    
    if(applyCouponBtn && discountCodeInput) {
        applyCouponBtn.addEventListener('click', function() {
            const code = discountCodeInput.value.trim();
            
            if(code === '') {
                couponMessage.innerHTML = '<span style="color: #dc3545;">Please enter a coupon code.</span>';
                return;
            }
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            // Simulate coupon check (replace with actual AJAX call)
            setTimeout(() => {
                // Reset button
                this.innerHTML = 'Apply';
                this.disabled = false;
                
                // For demo purposes, always show invalid coupon
                couponMessage.innerHTML = '<span style="color: #dc3545;">Invalid or expired coupon code.</span>';
                
                // In a real implementation, you would check the coupon code with the server
                // and update the order totals accordingly
            }, 1000);
        });
    }
    
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
