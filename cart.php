<?php
// Set page title
$pageTitle = "Shopping Cart";

// Include database connection
include 'includes/db_connect.php';

// Include header
include 'includes/header.php';

// Initialize variables
$cartItems = [];
$subtotal = 0;
$discount = 0;
$shipping = 0;
$total = 0;
$error = '';
$message = '';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Handle cart actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update quantity
        if (isset($_POST['update_cart'])) {
            foreach ($_POST['quantity'] as $cartId => $quantity) {
                if ($quantity > 0) {
                    $updateQuery = "UPDATE cart SET quantity = $quantity WHERE id = $cartId AND user_id = $userId";
                    mysqli_query($conn, $updateQuery);
                } else {
                    $deleteQuery = "DELETE FROM cart WHERE id = $cartId AND user_id = $userId";
                    mysqli_query($conn, $deleteQuery);
                }
            }
            $message = "Cart updated successfully.";
        }
        
        // Remove item
        if (isset($_POST['remove_item'])) {
            $cartId = $_POST['remove_item'];
            $deleteQuery = "DELETE FROM cart WHERE id = $cartId AND user_id = $userId";
            mysqli_query($conn, $deleteQuery);
            $message = "Item removed from cart.";
        }
        
        // Apply coupon
        if (isset($_POST['apply_coupon'])) {
            $couponCode = mysqli_real_escape_string($conn, $_POST['coupon_code']);
            
            // Check if coupon exists and is valid
            $couponQuery = "SELECT * FROM coupons WHERE code = '$couponCode' AND active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
            $couponResult = mysqli_query($conn, $couponQuery);
            
            if (mysqli_num_rows($couponResult) > 0) {
                $coupon = mysqli_fetch_assoc($couponResult);
                
                // Store coupon in session
                $_SESSION['coupon'] = $coupon;
                $message = "Coupon applied successfully.";
            } else {
                $error = "Invalid or expired coupon code.";
            }
        }
        
        // Remove coupon
        if (isset($_POST['remove_coupon'])) {
            unset($_SESSION['coupon']);
            $message = "Coupon removed.";
        }
    }
    
    // Get cart items
    $cartQuery = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image, p.stock_quantity 
                 FROM cart c 
                 JOIN products p ON c.product_id = p.id 
                 WHERE c.user_id = $userId";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if (mysqli_num_rows($cartResult) > 0) {
        while ($item = mysqli_fetch_assoc($cartResult)) {
            $cartItems[] = $item;
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Apply coupon discount if available
        if (isset($_SESSION['coupon'])) {
            $coupon = $_SESSION['coupon'];
            
            if ($coupon['discount_type'] === 'percentage') {
                $discount = $subtotal * ($coupon['discount_value'] / 100);
            } else {
                $discount = $coupon['discount_value'];
            }
            
            // Apply maximum discount if set
            if ($coupon['max_discount'] > 0 && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        }
        
        // Calculate shipping
        $shippingQuery = "SELECT * FROM shipping_settings WHERE id = 1";
        $shippingResult = mysqli_query($conn, $shippingQuery);
        $shippingSettings = mysqli_fetch_assoc($shippingResult);
        
        if ($subtotal >= $shippingSettings['free_shipping_threshold']) {
            $shipping = 0;
        } else {
            $shipping = $shippingSettings['base_shipping_cost'];
        }
        
        // Calculate total
        $total = $subtotal - $discount + $shipping;
    }
} else {
    // Handle guest cart (stored in session)
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Handle cart actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update quantity
        if (isset($_POST['update_cart'])) {
            foreach ($_POST['quantity'] as $productId => $quantity) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            $message = "Cart updated successfully.";
        }
        
        // Remove item
        if (isset($_POST['remove_item'])) {
            $productId = $_POST['remove_item'];
            unset($_SESSION['cart'][$productId]);
            $message = "Item removed from cart.";
        }
        
        // Apply coupon
        if (isset($_POST['apply_coupon'])) {
            $couponCode = mysqli_real_escape_string($conn, $_POST['coupon_code']);
            
            // Check if coupon exists and is valid
            $couponQuery = "SELECT * FROM coupons WHERE code = '$couponCode' AND active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
            $couponResult = mysqli_query($conn, $couponQuery);
            
            if (mysqli_num_rows($couponResult) > 0) {
                $coupon = mysqli_fetch_assoc($couponResult);
                
                // Store coupon in session
                $_SESSION['coupon'] = $coupon;
                $message = "Coupon applied successfully.";
            } else {
                $error = "Invalid or expired coupon code.";
            }
        }
        
        // Remove coupon
        if (isset($_POST['remove_coupon'])) {
            unset($_SESSION['coupon']);
            $message = "Coupon removed.";
        }
    }
    
    // Get cart items
    if (!empty($_SESSION['cart'])) {
        $productIds = array_keys($_SESSION['cart']);
        $productIdsStr = implode(',', $productIds);
        
        $productsQuery = "SELECT id, name, price, image, stock_quantity FROM products WHERE id IN ($productIdsStr)";
        $productsResult = mysqli_query($conn, $productsQuery);
        
        while ($product = mysqli_fetch_assoc($productsResult)) {
            $quantity = $_SESSION['cart'][$product['id']];
            $cartItems[] = [
                'id' => $product['id'],
                'product_id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity,
                'stock_quantity' => $product['stock_quantity']
            ];
            $subtotal += $product['price'] * $quantity;
        }
        
        // Apply coupon discount if available
        if (isset($_SESSION['coupon'])) {
            $coupon = $_SESSION['coupon'];
            
            if ($coupon['discount_type'] === 'percentage') {
                $discount = $subtotal * ($coupon['discount_value'] / 100);
            } else {
                $discount = $coupon['discount_value'];
            }
            
            // Apply maximum discount if set
            if ($coupon['max_discount'] > 0 && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        }
        
        // Calculate shipping
        $shippingQuery = "SELECT * FROM shipping_settings WHERE id = 1";
        $shippingResult = mysqli_query($conn, $shippingQuery);
        $shippingSettings = mysqli_fetch_assoc($shippingResult);
        
        if ($subtotal >= $shippingSettings['free_shipping_threshold']) {
            $shipping = 0;
        } else {
            $shipping = $shippingSettings['base_shipping_cost'];
        }
        
        // Calculate total
        $total = $subtotal - $discount + $shipping;
    }
}

// Get site options for customization
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}
?>

<!-- Main Content -->
<main class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1>Shopping Cart</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; <span>Shopping Cart</span>
            </div>
        </div>
        
        <?php if($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if(empty($cartItems)): ?>
        <div class="empty-cart <?php echo $isMobile ? 'mobile-empty-state' : ''; ?>">
            <?php if($isMobile): ?>
            <div class="mobile-empty-state-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="mobile-empty-state-title">Your cart is empty</div>
            <div class="mobile-empty-state-text">Looks like you haven't added any products to your cart yet.</div>
            <?php else: ?>
            <i class="fas fa-shopping-cart"></i>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any products to your cart yet.</p>
            <?php endif; ?>
            <a href="all-items.php" class="btn btn-primary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Continue Shopping</a>
        </div>
        <?php else: ?>
        <div class="cart-container">
            <div class="cart-items">
                <form action="" method="POST" id="cart-form">
                    <?php if($isMobile): ?>
                    <!-- Mobile Cart View -->
                    <?php foreach($cartItems as $item): ?>
                    <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                        <div class="cart-item-image">
                            <img src="uploads/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="cart-item-details">
                            <h3 class="cart-item-title"><?php echo $item['name']; ?></h3>
                            <div class="cart-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            
                            <div class="cart-item-quantity">
                                <div class="quantity-input mobile">
                                    <button type="button" class="quantity-decrease touch-friendly">-</button>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                    <button type="button" class="quantity-increase touch-friendly">+</button>
                                </div>
                                
                                <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="cart-item-subtotal">
                                Subtotal: <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        </div>
                        
                        <!-- Swipe to delete functionality is handled by JS -->
                        <div class="cart-item-delete">
                            <i class="fas fa-trash"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-actions">
                        <button type="submit" name="update_cart" class="btn btn-outline-primary mobile-btn">Update Cart</button>
                    </div>
                    <?php else: ?>
                    <!-- Desktop Cart View -->
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cartItems as $item): ?>
                            <tr>
                                <td class="product-col">
                                    <div class="product-info">
                                        <img src="uploads/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                        <div class="product-name"><?php echo $item['name']; ?></div>
                                    </div>
                                </td>
                                <td class="price-col">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td class="quantity-col">
                                    <div class="quantity-input">
                                        <button type="button" class="quantity-decrease">-</button>
                                        <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                        <button type="button" class="quantity-increase">+</button>
                                    </div>
                                </td>
                                <td class="subtotal-col">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="action-col">
                                    <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>" class="remove-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions">
                        <a href="all-items.php" class="btn btn-outline-secondary">Continue Shopping</a>
                        <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="cart-summary <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if($discount > 0): ?>
                <div class="summary-row discount">
                    <span>Discount</span>
                    <span>-₹<?php echo number_format($discount, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?php echo $shipping > 0 ? '₹' . number_format($shipping, 2) : 'Free'; ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
                
                <!-- Coupon Code -->
                <div class="coupon-section">
                    <?php if(isset($_SESSION['coupon'])): ?>
                    <div class="applied-coupon">
                        <div class="coupon-info">
                            <span class="coupon-code"><?php echo $_SESSION['coupon']['code']; ?></span>
                            <span class="coupon-discount">
                                <?php 
                                if($_SESSION['coupon']['discount_type'] === 'percentage') {
                                    echo $_SESSION['coupon']['discount_value'] . '% off';
                                } else {
                                    echo '₹' . number_format($_SESSION['coupon']['discount_value'], 2) . ' off';
                                }
                                ?>
                            </span>
                        </div>
                        <form action="" method="POST">
                            <button type="submit" name="remove_coupon" class="remove-coupon">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <form action="" method="POST" class="coupon-form">
                        <input type="text" name="coupon_code" placeholder="Enter coupon code" class="<?php echo $isMobile ? 'mobile-input' : ''; ?>" required>
                        <button type="submit" name="apply_coupon" class="btn btn-secondary <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Apply</button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <div class="checkout-button">
                    <a href="checkout.php" class="btn btn-primary btn-block <?php echo $isMobile ? 'mobile-btn' : ''; ?>">Proceed to Checkout</a>
                </div>
                
                <div class="payment-methods-info">
                    <h3>We Accept</h3>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-amex"></i>
                        <i class="fab fa-cc-discover"></i>
                        <i class="fab fa-google-pay"></i>
                        <i class="fab fa-apple-pay"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(isset($settings['product_show_related']) && $settings['product_show_related']): ?>
        <div class="related-products">
            <h2><?php echo isset($settings['product_related_title']) ? $settings['product_related_title'] : 'You May Also Like'; ?></h2>
            
            <div class="products-grid <?php echo $isMobile ? 'mobile-products-grid' : ''; ?>">
                <?php
                // Get related products based on cart items
                $productIds = array_column($cartItems, 'product_id');
                $productIdsStr = implode(',', $productIds);
                
                // Get categories of products in cart
                $categoryQuery = "SELECT DISTINCT c.id 
                                 FROM categories c 
                                 JOIN products p ON p.category_id = c.id 
                                 WHERE p.id IN ($productIdsStr)";
                $categoryResult = mysqli_query($conn, $categoryQuery);
                $categoryIds = [];
                
                while ($category = mysqli_fetch_assoc($categoryResult)) {
                    $categoryIds[] = $category['id'];
                }
                
                if (!empty($categoryIds)) {
                    $categoryIdsStr = implode(',', $categoryIds);
                    
                    // Get related products from same categories, excluding cart items
                    $relatedQuery = "SELECT * FROM products 
                                    WHERE category_id IN ($categoryIdsStr) 
                                    AND id NOT IN ($productIdsStr) 
                                    AND active = 1 
                                    ORDER BY RAND() 
                                    LIMIT 4";
                    $relatedResult = mysqli_query($conn, $relatedQuery);
                    
                    while ($product = mysqli_fetch_assoc($relatedResult)):
                ?>
                <div class="product-card <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                        <img src="uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/products/' . $product['image'] . '"' : ''; ?>>
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="price">₹<?php echo $product['price']; ?></p>
                    </a>
                    <div class="product-card-actions">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                        <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php 
                    endwhile;
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Include mobile bottom navigation for mobile devices -->
<?php if($isMobile): ?>
<?php include 'includes/mobile_bottom_nav.php'; ?>
<?php endif; ?>

<!-- Include footer -->
<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity buttons functionality
        const quantityInputs = document.querySelectorAll('.quantity-input');
        
        quantityInputs.forEach(function(wrapper) {
            const input = wrapper.querySelector('input');
            const decreaseBtn = wrapper.querySelector('.quantity-decrease');
            const increaseBtn = wrapper.querySelector('.quantity-increase');
            
            if (input && decreaseBtn && increaseBtn) {
                decreaseBtn.addEventListener('click', function() {
                    let value = parseInt(input.value);
                    if (value > 1) {
                        input.value = value - 1;
                    }
                });
                
                increaseBtn.addEventListener('click', function() {
                    let value = parseInt(input.value);
                    let max = parseInt(input.getAttribute('max'));
                    
                    if (value < max) {
                        input.value = value + 1;
                    }
                });
            }
        });
        
        // Add to cart functionality for related products
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        
        addToCartButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Send AJAX request
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        this.innerHTML = '<i class="fas fa-check"></i> Added';
                        
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                            cartCount.style.display = 'inline-block';
                        }
                        
                        // Reset button after delay
                        setTimeout(() => {
                            this.innerHTML = 'Add to Cart';
                            this.disabled = false;
                        }, 2000);
                    } else {
                        // Show error
                        this.innerHTML = 'Error';
                        this.disabled = false;
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = 'Error';
                    this.disabled = false;
                });
            });
        });
        
        // Handle swipe to delete on mobile
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        if (isMobile) {
            const cartItems = document.querySelectorAll('.cart-item');
            
            cartItems.forEach(item => {
                const deleteButton = item.querySelector('.cart-item-delete');
                
                if (deleteButton) {
                    deleteButton.addEventListener('click', function() {
                        const itemId = item.getAttribute('data-id');
                        const form = document.getElementById('cart-form');
                        
                        // Create hidden input for remove_item
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'remove_item';
                        input.value = itemId;
                        
                        form.appendChild(input);
                        form.submit();
                    });
                }
            });
        }
    });
</script>
