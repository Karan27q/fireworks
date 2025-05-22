<?php
/**
 * Order related functions
 */

/**
 * Send WhatsApp notification for new order
 * 
 * @param int $orderId The order ID
 * @return bool True if notification sent successfully
 */
function sendOrderWhatsAppNotification($orderId) {
    global $conn;
    
    // Get site settings for WhatsApp number
    $settingsQuery = "SELECT whatsapp, site_name FROM site_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    $ownerWhatsApp = $settings['whatsapp'];
    $siteName = $settings['site_name'];
    
    if(empty($ownerWhatsApp)) {
        return false; // No WhatsApp number configured
    }
    
    // Get order details
    $orderQuery = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id 
                  WHERE o.id = $orderId";
    $orderResult = mysqli_query($conn, $orderQuery);
    
    if(mysqli_num_rows($orderResult) === 0) {
        return false; // Order not found
    }
    
    $order = mysqli_fetch_assoc($orderResult);
    
    // Get order items
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = $orderId";
    $itemsResult = mysqli_query($conn, $itemsQuery);
    $orderItems = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);
    
    // Format order items
    $itemsList = "";
    foreach($orderItems as $item) {
        $itemsList .= "• {$item['product_name']} x {$item['quantity']} - ₹" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }
    
    // Format shipping address
    $shippingAddress = "{$order['shipping_address']}, {$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}";
    
    // Create WhatsApp message
    $message = "*NEW ORDER #{$orderId} - {$siteName}*\n\n";
    $message .= "*Customer Details:*\n";
    $message .= "Name: {$order['customer_name']}\n";
    $message .= "Phone: {$order['customer_phone']}\n";
    $message .= "Email: {$order['customer_email']}\n\n";
    
    $message .= "*Shipping Address:*\n";
    $message .= "{$shippingAddress}\n\n";
    
    $message .= "*Order Items:*\n";
    $message .= "{$itemsList}\n";
    
    $message .= "*Order Summary:*\n";
    $message .= "Subtotal: ₹" . number_format($order['subtotal'], 2) . "\n";
    
    if($order['discount_amount'] > 0) {
        $message .= "Discount: -₹" . number_format($order['discount_amount'], 2) . "\n";
    }
    
    $message .= "Shipping: ₹" . number_format($order['shipping_amount'], 2) . "\n";
    $message .= "Tax: ₹" . number_format($order['tax_amount'], 2) . "\n";
    $message .= "Total: ₹" . number_format($order['total_amount'], 2) . "\n\n";
    
    $message .= "Payment Method: " . ucfirst($order['payment_method']) . "\n";
    $message .= "Order Date: " . date('d M Y, h:i A', strtotime($order['created_at']));
    
    // Encode message for URL
    $encodedMessage = urlencode($message);
    
    // Create WhatsApp API URL
    $whatsappUrl = "https://api.whatsapp.com/send?phone={$ownerWhatsApp}&text={$encodedMessage}";
    
    // For direct integration with WhatsApp Business API, you would use cURL here
    // For now, we'll just return the URL which can be used to send the message manually
    // or through JavaScript in the browser
    
    return $whatsappUrl;
}

/**
 * Create a new order
 * 
 * @param int $userId User ID
 * @param array $orderData Order data
 * @param array $items Order items
 * @return int|bool Order ID if successful, false otherwise
 */
function createOrder($userId, $orderData, $items) {
    global $conn;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert order
        $query = "INSERT INTO orders (
                    user_id, 
                    subtotal,
                    discount_id,
                    discount_amount,
                    shipping_amount,
                    tax_amount,
                    total_amount,
                    shipping_address,
                    shipping_city,
                    shipping_state,
                    shipping_pincode,
                    shipping_phone,
                    payment_method,
                    payment_status,
                    status,
                    notes,
                    created_at
                ) VALUES (
                    $userId,
                    {$orderData['subtotal']},
                    " . ($orderData['discount_id'] ? $orderData['discount_id'] : "NULL") . ",
                    {$orderData['discount_amount']},
                    {$orderData['shipping_amount']},
                    {$orderData['tax_amount']},
                    {$orderData['total_amount']},
                    '{$orderData['shipping_address']}',
                    '{$orderData['shipping_city']}',
                    '{$orderData['shipping_state']}',
                    '{$orderData['shipping_pincode']}',
                    '{$orderData['shipping_phone']}',
                    '{$orderData['payment_method']}',
                    '{$orderData['payment_status']}',
                    'pending',
                    " . ($orderData['notes'] ? "'{$orderData['notes']}'" : "NULL") . ",
                    NOW()
                )";
        
        $result = mysqli_query($conn, $query);
        
        if(!$result) {
            throw new Exception("Failed to create order: " . mysqli_error($conn));
        }
        
        $orderId = mysqli_insert_id($conn);
        
        // Insert order items
        foreach($items as $item) {
            $query = "INSERT INTO order_items (
                        order_id,
                        product_id,
                        quantity,
                        price
                    ) VALUES (
                        $orderId,
                        {$item['product_id']},
                        {$item['quantity']},
                        {$item['price']}
                    )";
            
            $result = mysqli_query($conn, $query);
            
            if(!$result) {
                throw new Exception("Failed to add order item: " . mysqli_error($conn));
            }
            
            // Update product stock
            $query = "UPDATE products 
                      SET stock_quantity = stock_quantity - {$item['quantity']} 
                      WHERE id = {$item['product_id']}";
            
            $result = mysqli_query($conn, $query);
            
            if(!$result) {
                throw new Exception("Failed to update product stock: " . mysqli_error($conn));
            }
        }
        
        // If discount was used, update usage count
        if($orderData['discount_id']) {
            // Update discount usage count
            $query = "UPDATE discounts 
                      SET usage_count = usage_count + 1 
                      WHERE id = {$orderData['discount_id']}";
            
            $result = mysqli_query($conn, $query);
            
            if(!$result) {
                throw new Exception("Failed to update discount usage: " . mysqli_error($conn));
            }
            
            // Update user discount usage
            $query = "INSERT INTO customer_discounts (user_id, discount_id, usage_count, last_used_at) 
                      VALUES ($userId, {$orderData['discount_id']}, 1, NOW())
                      ON DUPLICATE KEY UPDATE 
                      usage_count = usage_count + 1,
                      last_used_at = NOW()";
            
            $result = mysqli_query($conn, $query);
            
            if(!$result) {
                throw new Exception("Failed to update user discount usage: " . mysqli_error($conn));
            }
        }
        
        // Clear user's cart
        $query = "DELETE FROM cart WHERE user_id = $userId";
        $result = mysqli_query($conn, $query);
        
        if(!$result) {
            throw new Exception("Failed to clear cart: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Send WhatsApp notification
        sendOrderWhatsAppNotification($orderId);
        
        return $orderId;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Update order status
 * 
 * @param int $orderId Order ID
 * @param string $status New status
 * @param array $data Additional data to update
 * @return bool True if successful
 */
function updateOrderStatus($orderId, $status, $data = []) {
    global $conn;
    
    $updateFields = ["status = '$status'"];
    
    // Add additional fields to update
    foreach($data as $field => $value) {
        if($value !== null) {
            $updateFields[] = "$field = '$value'";
        }
    }
    
    $updateFields[] = "updated_at = NOW()";
    
    $query = "UPDATE orders SET " . implode(", ", $updateFields) . " WHERE id = $orderId";
    $result = mysqli_query($conn, $query);
    
    return $result ? true : false;
}
