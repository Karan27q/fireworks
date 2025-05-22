<?php
/**
 * WhatsApp Notification System
 * 
 * This file contains functions for sending WhatsApp notifications for orders and other events.
 */

// Send WhatsApp message using API
function send_whatsapp_message($recipient, $message) {
    // Get WhatsApp settings
    $api_key = get_option('whatsapp_api_key', '');
    $instance_id = get_option('whatsapp_instance_id', '');
    
    if (empty($api_key) || empty($instance_id)) {
        return false;
    }
    
    // Format phone number
    if (substr($recipient, 0, 1) !== '+') {
        $recipient = '+' . $recipient;
    }
    
    // Prepare API request
    // This is a generic implementation - replace with your actual WhatsApp API provider
    $url = "https://api.whatsapp-provider.com/send";
    $data = [
        'api_key' => $api_key,
        'instance_id' => $instance_id,
        'number' => $recipient,
        'message' => $message,
        'type' => 'text'
    ];
    
    // Send request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("WhatsApp API Error: " . $error);
        return false;
    }
    
    $result = json_decode($response, true);
    
    // Log the result
    $log_query = "INSERT INTO notification_logs (type, recipient, message, status, response) 
                 VALUES ('whatsapp', '$recipient', '" . mysqli_real_escape_string(get_db_connection(), $message) . "', 
                 '" . ($result['success'] ? 'success' : 'failed') . "', 
                 '" . mysqli_real_escape_string(get_db_connection(), $response) . "')";
    mysqli_query(get_db_connection(), $log_query);
    
    return isset($result['success']) && $result['success'];
}

// Get database connection
function get_db_connection() {
    global $conn;
    return $conn;
}

// Get option from database
function get_option($option_name, $default = '') {
    $query = "SELECT whatsapp FROM site_settings WHERE id = 1";
    $result = mysqli_query(get_db_connection(), $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['whatsapp'];
    }
    
    return $default;
}

// Send order notification to admin
function send_order_notification_to_admin($order_id) {
    // Get order details
    $order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = $order_id";
    $order_result = mysqli_query(get_db_connection(), $order_query);
    
    if (!$order_result || mysqli_num_rows($order_result) == 0) {
        return false;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // Get order items
    $items_query = "SELECT oi.*, p.name as product_name 
                   FROM order_items oi 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = $order_id";
    $items_result = mysqli_query(get_db_connection(), $items_query);
    $items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
    
    // Get admin phone number
    $admin_phone = get_option('admin_phone', '');
    
    if (empty($admin_phone)) {
        return false;
    }
    
    // Prepare message
    $message = "🎆 *NEW ORDER #$order_id - VAMSI CRACKERS* 🎆\n\n";
    $message .= "*Customer:* {$order['customer_name']}\n";
    $message .= "*Phone:* {$order['customer_phone']}\n";
    $message .= "*Email:* {$order['customer_email']}\n";
    $message .= "*Amount:* ₹" . number_format($order['total_amount'], 2) . "\n";
    $message .= "*Payment Method:* " . ucfirst($order['payment_method']) . "\n";
    $message .= "*Status:* " . ucfirst($order['status']) . "\n\n";

    $message .= "*Order Items:*\n";
    foreach ($items as $item) {
        $message .= "- {$item['product_name']} x {$item['quantity']} = ₹" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }

    $message .= "\n*Shipping Address:*\n";
    $message .= "{$order['shipping_address']}\n";
    $message .= "{$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}\n\n";

    $message .= "View order details in admin panel: " . get_option('site_url', '') . "/admin/view-order.php?id=$order_id";
    
    // Send message
    return send_whatsapp_message($admin_phone, $message);
}

// Send order confirmation to customer
function send_order_confirmation_to_customer($order_id) {
    // Get order details
    $order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = $order_id";
    $order_result = mysqli_query(get_db_connection(), $order_query);
    
    if (!$order_result || mysqli_num_rows($order_result) == 0) {
        return false;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // Check if customer has phone number
    if (empty($order['customer_phone'])) {
        return false;
    }
    
    // Get site name
    $site_name = get_option('site_name', 'Fireworks Shop');
    
    // Prepare message
    $message = "🎆 *Thank you for your order with $site_name!* 🎆\n\n";
    $message .= "*Order #$order_id has been received*\n\n";
    $message .= "*Amount:* ₹" . number_format($order['total_amount'], 2) . "\n";
    $message .= "*Payment Method:* " . ucfirst($order['payment_method']) . "\n";
    $message .= "*Status:* " . ucfirst($order['status']) . "\n\n";
    
    $message .= "You can track your order status by logging into your account.\n\n";
    $message .= "If you have any questions, please contact our customer support.\n\n";
    $message .= "Thank you for shopping with us!";
    
    // Send message
    return send_whatsapp_message($order['customer_phone'], $message);
}

// Send order status update to customer
function send_order_status_update($order_id, $new_status) {
    // Get order details
    $order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = $order_id";
    $order_result = mysqli_query(get_db_connection(), $order_query);
    
    if (!$order_result || mysqli_num_rows($order_result) == 0) {
        return false;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // Check if customer has phone number
    if (empty($order['customer_phone'])) {
        return false;
    }
    
    // Get site name
    $site_name = get_option('site_name', 'Fireworks Shop');
    
    // Prepare message based on status
    $message = "🎆 *Order Status Update from $site_name* 🎆\n\n";
    $message .= "*Order #$order_id*\n\n";
    
    switch ($new_status) {
        case 'processing':
            $message .= "Your order is now being processed. We'll notify you once it's ready for shipping.\n\n";
            break;
        case 'shipped':
            // Get tracking info if available
            $tracking_query = "SELECT tracking_number, courier_name FROM order_shipping WHERE order_id = $order_id";
            $tracking_result = mysqli_query(get_db_connection(), $tracking_query);
            $tracking = mysqli_fetch_assoc($tracking_result);
            
            $message .= "Your order has been shipped!\n\n";
            
            if ($tracking && !empty($tracking['tracking_number'])) {
                $message .= "*Courier:* {$tracking['courier_name']}\n";
                $message .= "*Tracking Number:* {$tracking['tracking_number']}\n\n";
            }
            
            $message .= "You can track your package using the tracking number above.\n\n";
            break;
        case 'delivered':
            $message .= "Your order has been delivered. We hope you enjoy your purchase!\n\n";
            $message .= "Thank you for shopping with us. Please leave a review if you're satisfied with our products and service.\n\n";
            break;
        case 'cancelled':
            $message .= "Your order has been cancelled as requested.\n\n";
            $message .= "If you have any questions, please contact our customer support.\n\n";
            break;
        default:
            $message .= "Your order status has been updated to: " . ucfirst($new_status) . "\n\n";
    }
    
    $message .= "If you have any questions, please contact our customer support.\n\n";
    $message .= "Thank you for shopping with us!";
    
    // Send message
    return send_whatsapp_message($order['customer_phone'], $message);
}

// Test WhatsApp notification
function test_whatsapp_notification($phone_number) {
    $message = "🎆 *Test Message from Vamsi Crackers* 🎆\n\n";
    $message .= "This is a test message to verify that the WhatsApp notification system is working correctly.\n\n";
    $message .= "If you received this message, the system is configured properly.";
    
    return send_whatsapp_message($phone_number, $message);
}
