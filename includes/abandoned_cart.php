<?php
/**
 * Abandoned Cart Recovery System
 * 
 * This file contains functions for tracking and recovering abandoned shopping carts.
 */

// Track cart activity and detect abandonment
function track_cart_activity() {
    global $conn;
    
    // Only track if cart has items
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return;
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    $email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
    $phone = isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : null;
    
    // Calculate cart total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    // Check if cart already exists for this user/session
    $cart_id = null;
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT id FROM abandoned_carts WHERE user_id = ? AND recovery_status = 'pending'");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM abandoned_carts WHERE session_id = ? AND recovery_status = 'pending'");
        $stmt->bind_param("s", $session_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $cart_id = $row['id'];
        
        // Update existing cart
        $stmt = $conn->prepare("
            UPDATE abandoned_carts 
            SET total_amount = ?, updated_at = NOW(), email = ?, phone = ?
            WHERE id = ?
        ");
        $stmt->bind_param("dssi", $total_amount, $email, $phone, $cart_id);
        $stmt->execute();
    } else {
        // Create new abandoned cart
        $stmt = $conn->prepare("
            INSERT INTO abandoned_carts 
            (user_id, session_id, email, phone, total_amount) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssd", $user_id, $session_id, $email, $phone, $total_amount);
        $stmt->execute();
        $cart_id = $stmt->insert_id;
    }
    
    $stmt->close();
    
    // Clear existing cart items
    $conn->query("DELETE FROM abandoned_cart_items WHERE cart_id = $cart_id");
    
    // Add cart items
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $quantity = $item['quantity'];
        $price = $item['price'];
        $total = $price * $quantity;
        
        $stmt = $conn->prepare("
            INSERT INTO abandoned_cart_items 
            (cart_id, product_id, quantity, price, total) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiddd", $cart_id, $product_id, $quantity, $price, $total);
        $stmt->execute();
        $stmt->close();
    }
    
    // Generate recovery token if not exists
    if (!isset($_SESSION['recovery_token'])) {
        $recovery_token = bin2hex(random_bytes(16));
        $_SESSION['recovery_token'] = $recovery_token;
        
        $stmt = $conn->prepare("UPDATE abandoned_carts SET recovery_token = ? WHERE id = ?");
        $stmt->bind_param("si", $recovery_token, $cart_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Process abandoned carts and send notifications
function process_abandoned_carts() {
    global $conn;
    
    // Find abandoned carts that are at least 1 hour old and have not been recovered
    $query = "
        SELECT * FROM abandoned_carts 
        WHERE recovery_status = 'pending' 
        AND (email IS NOT NULL OR phone IS NOT NULL)
        AND TIMESTAMPDIFF(HOUR, updated_at, NOW()) >= 1
        AND (
            last_notification_sent IS NULL 
            OR (
                notification_count < 3 
                AND TIMESTAMPDIFF(DAY, last_notification_sent, NOW()) >= 1
            )
        )
    ";
    
    $result = $conn->query($query);
    
    while ($cart = $result->fetch_assoc()) {
        // Get cart items
        $items_query = "
            SELECT aci.*, p.name, p.image 
            FROM abandoned_cart_items aci
            JOIN products p ON aci.product_id = p.id
            WHERE aci.cart_id = {$cart['id']}
        ";
        
        $items_result = $conn->query($items_query);
        $items = [];
        
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        // Send email notification if email exists
        if ($cart['email']) {
            send_abandoned_cart_email($cart, $items);
        }
        
        // Send WhatsApp notification if phone exists and WhatsApp is enabled
        if ($cart['phone']) {
            $whatsapp_enabled = get_option('whatsapp_notifications_enabled', '0');
            
            if ($whatsapp_enabled == '1') {
                send_abandoned_cart_whatsapp($cart, $items);
            }
        }
        
        // Update notification status
        $notification_count = $cart['notification_count'] + 1;
        
        $stmt = $conn->prepare("
            UPDATE abandoned_carts 
            SET last_notification_sent = NOW(), notification_count = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $notification_count, $cart['id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Send abandoned cart recovery email
function send_abandoned_cart_email($cart, $items) {
    global $conn;
    
    // Get email template
    $template = get_option('abandoned_cart_email_template', '');
    
    if (empty($template)) {
        // Default template
        $template = "
            <h2>You left items in your cart</h2>
            <p>We noticed you have items in your shopping cart but didn't complete your purchase.</p>
            <p>Your cart contains:</p>
            <ul>
                {{CART_ITEMS}}
            </ul>
            <p>Total: {{CART_TOTAL}}</p>
            <p><a href='{{RECOVERY_LINK}}'>Click here to complete your purchase</a></p>
        ";
    }
    
    // Build cart items HTML
    $items_html = '';
    foreach ($items as $item) {
        $items_html .= "<li>{$item['name']} - {$item['quantity']} x ₹{$item['price']} = ₹{$item['total']}</li>";
    }
    
    // Replace placeholders
    $site_url = get_option('site_url', '');
    $recovery_link = $site_url . "/recover-cart.php?token=" . $cart['recovery_token'];
    $site_name = get_option('site_name', 'Fireworks E-commerce');
    
    $message = str_replace('{{CART_ITEMS}}', $items_html, $template);
    $message = str_replace('{{CART_TOTAL}}', '₹' . $cart['total_amount'], $message);
    $message = str_replace('{{RECOVERY_LINK}}', $recovery_link, $message);
    $message = str_replace('{{CUSTOMER_NAME}}', $cart['email'], $message);
    $message = str_replace('{{SITE_NAME}}', $site_name, $message);
    
    // Send email
    $subject = "Complete your purchase at $site_name";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $site_name . ' <' . get_option('contact_email', 'noreply@example.com') . '>' . "\r\n";
    
    mail($cart['email'], $subject, $message, $headers);
    
    // Log notification
    log_notification('abandoned_cart', $cart['id'], $cart['email'], $message);
}

// Send abandoned cart WhatsApp notification
function send_abandoned_cart_whatsapp($cart, $items) {
    global $conn;
    
    // Get WhatsApp settings
    $api_key = get_option('whatsapp_api_key', '');
    $instance_id = get_option('whatsapp_instance_id', '');
    $phone_number = get_option('whatsapp_phone_number', '');
    
    if (empty($api_key) || empty($instance_id) || empty($phone_number)) {
        return false;
    }
    
    // Prepare message
    $site_name = get_option('site_name', 'Fireworks E-commerce');
    $site_url = get_option('site_url', '');
    $recovery_link = $site_url . "/recover-cart.php?token=" . $cart['recovery_token'];
    
    $message = "Hello from $site_name!\n\n";
    $message .= "We noticed you have items in your cart but didn't complete your purchase.\n\n";
    $message .= "Your cart contains:\n";
    
    foreach ($items as $item) {
        $message .= "- {$item['name']} - {$item['quantity']} x ₹{$item['price']} = ₹{$item['total']}\n";
    }
    
    $message .= "\nTotal: ₹{$cart['total_amount']}\n\n";
    $message .= "Complete your purchase here: $recovery_link";
    
    // Send WhatsApp message
    $recipient = $cart['phone'];
    if (substr($recipient, 0, 1) !== '+') {
        $recipient = '+' . $recipient;
    }
    
    $response = send_whatsapp_message($recipient, $message);
    
    // Log notification
    log_notification('abandoned_cart', $cart['id'], $recipient, $message, $response);
    
    return true;
}

// Recover abandoned cart
function recover_abandoned_cart($token) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM abandoned_carts 
        WHERE recovery_token = ? AND recovery_status = 'pending'
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($cart = $result->fetch_assoc()) {
        // Get cart items
        $items_query = "
            SELECT aci.*, p.name, p.image 
            FROM abandoned_cart_items aci
            JOIN products p ON aci.product_id = p.id
            WHERE aci.cart_id = {$cart['id']}
        ";
        
        $items_result = $conn->query($items_query);
        
        // Restore cart in session
        $_SESSION['cart'] = [];
        
        while ($item = $items_result->fetch_assoc()) {
            $_SESSION['cart'][$item['product_id']] = [
                'id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'image' => $item['image']
            ];
        }
        
        // Update recovery status
        $stmt = $conn->prepare("
            UPDATE abandoned_carts 
            SET recovery_status = 'recovered'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $cart['id']);
        $stmt->execute();
        
        return true;
    }
    
    return false;
}

// Mark cart as recovered when order is placed
function mark_cart_as_recovered() {
    global $conn;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    
    if ($user_id) {
        $stmt = $conn->prepare("
            UPDATE abandoned_carts 
            SET recovery_status = 'recovered'
            WHERE user_id = ? AND recovery_status = 'pending'
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE abandoned_carts 
            SET recovery_status = 'recovered'
            WHERE session_id = ? AND recovery_status = 'pending'
        ");
        $stmt->bind_param("s", $session_id);
    }
    
    $stmt->execute();
    $stmt->close();
}

// Log notification
function log_notification($notification_type, $reference_id, $recipient, $message, $response = null) {
    global $conn;
    
    $status = $response ? 'sent' : 'failed';
    
    $stmt = $conn->prepare("
        INSERT INTO notification_logs 
        (notification_type, reference_id, recipient, message, status, response) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissss", $notification_type, $reference_id, $recipient, $message, $status, $response);
    $stmt->execute();
    $stmt->close();
}

// Clean up expired abandoned carts
function cleanup_abandoned_carts() {
    global $conn;
    
    // Mark carts as expired if they are older than 30 days
    $conn->query("
        UPDATE abandoned_carts 
        SET recovery_status = 'expired'
        WHERE recovery_status = 'pending' 
        AND TIMESTAMPDIFF(DAY, updated_at, NOW()) > 30
    ");
}

// Initialize abandoned cart system
function init_abandoned_cart_system() {
    // Track cart activity on every page load if user has items in cart
    track_cart_activity();
    
    // Process abandoned carts once per hour
    $last_processed = get_option('last_abandoned_cart_process', '');
    $current_hour = date('Y-m-d H');
    
    if ($last_processed != $current_hour) {
        process_abandoned_carts();
        cleanup_abandoned_carts();
        update_option('last_abandoned_cart_process', $current_hour);
    }
}

// Initialize the abandoned cart system
init_abandoned_cart_system();
