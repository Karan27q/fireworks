<?php
/**
 * Loyalty program related functions
 */

/**
 * Award loyalty points to a user
 * 
 * @param int $userId User ID
 * @param int $points Number of points to award
 * @param string $transactionType Type of transaction (earn, redeem, expire, adjust)
 * @param int|null $referenceId Reference ID (e.g., order ID)
 * @param string|null $referenceType Reference type (e.g., order, birthday, welcome)
 * @param string|null $description Description of the transaction
 * @return bool True if successful
 */
function awardLoyaltyPoints($userId, $points, $transactionType, $referenceId = null, $referenceType = null, $description = null) {
    global $conn;
    
    // Check if loyalty program is enabled
    $settingsQuery = "SELECT enabled FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings || !$settings['enabled']) {
        return false; // Loyalty program is disabled
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update user's loyalty points
        $updateQuery = "UPDATE users SET loyalty_points = loyalty_points + $points WHERE id = $userId";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if(!$updateResult) {
            throw new Exception("Failed to update user's loyalty points: " . mysqli_error($conn));
        }
        
        // Log the transaction
        $description = mysqli_real_escape_string($conn, $description);
        $referenceType = $referenceType ? "'" . mysqli_real_escape_string($conn, $referenceType) . "'" : "NULL";
        $referenceId = $referenceId ? $referenceId : "NULL";
        
        $logQuery = "INSERT INTO loyalty_transactions (user_id, points, transaction_type, reference_id, reference_type, description, created_at) 
                    VALUES ($userId, $points, '$transactionType', $referenceId, $referenceType, '$description', NOW())";
        $logResult = mysqli_query($conn, $logQuery);
        
        if(!$logResult) {
            throw new Exception("Failed to log loyalty transaction: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Calculate loyalty points for an order
 * 
 * @param int $orderId Order ID
 * @return bool True if successful
 */
function calculateOrderLoyaltyPoints($orderId) {
    global $conn;
    
    // Get order details
    $orderQuery = "SELECT o.*, u.id as user_id FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $orderId";
    $orderResult = mysqli_query($conn, $orderQuery);
    
    if(mysqli_num_rows($orderResult) === 0) {
        return false; // Order not found
    }
    
    $order = mysqli_fetch_assoc($orderResult);
    $userId = $order['user_id'];
    $orderTotal = $order['total_amount'];
    
    // Get loyalty settings
    $settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings || !$settings['enabled']) {
        return false; // Loyalty program is disabled
    }
    
    // Calculate points based on order total
    $pointsPerInr = $settings['points_per_inr'];
    $points = floor($orderTotal * $pointsPerInr);
    
    // Award points to user
    $description = "Points earned for order #$orderId";
    return awardLoyaltyPoints($userId, $points, 'earn', $orderId, 'order', $description);
}

/**
 * Award welcome points to a new user
 * 
 * @param int $userId User ID
 * @return bool True if successful
 */
function awardWelcomePoints($userId) {
    global $conn;
    
    // Get loyalty settings
    $settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings || !$settings['enabled'] || $settings['welcome_points'] <= 0) {
        return false; // Loyalty program is disabled or no welcome points
    }
    
    $points = $settings['welcome_points'];
    $description = "Welcome bonus points";
    
    return awardLoyaltyPoints($userId, $points, 'earn', null, 'welcome', $description);
}

/**
 * Award birthday points to a user
 * 
 * @param int $userId User ID
 * @return bool True if successful
 */
function awardBirthdayPoints($userId) {
    global $conn;
    
    // Get user details
    $userQuery = "SELECT * FROM users WHERE id = $userId";
    $userResult = mysqli_query($conn, $userQuery);
    
    if(mysqli_num_rows($userResult) === 0) {
        return false; // User not found
    }
    
    $user = mysqli_fetch_assoc($userResult);
    
    // Check if user has a birthday
    if(!$user['date_of_birth']) {
        return false; // No birthday set
    }
    
    // Get current date
    $currentDate = date('Y-m-d');
    $currentYear = date('Y');
    
    // Get user's birthday for current year
    $birthdayMonth = date('m', strtotime($user['date_of_birth']));
    $birthdayDay = date('d', strtotime($user['date_of_birth']));
    $birthdayThisYear = "$currentYear-$birthdayMonth-$birthdayDay";
    
    // Check if today is user's birthday
    if($currentDate != $birthdayThisYear) {
        return false; // Not user's birthday
    }
    
    // Check if birthday points already awarded this year
    if($user['last_birthday_points_date'] == $birthdayThisYear) {
        return false; // Already awarded birthday points this year
    }
    
    // Get loyalty settings
    $settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings || !$settings['enabled'] || $settings['birthday_points'] <= 0) {
        return false; // Loyalty program is disabled or no birthday points
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update last birthday points date
        $updateQuery = "UPDATE users SET last_birthday_points_date = '$birthdayThisYear' WHERE id = $userId";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if(!$updateResult) {
            throw new Exception("Failed to update last birthday points date: " . mysqli_error($conn));
        }
        
        // Award points
        $points = $settings['birthday_points'];
        $description = "Birthday bonus points";
        
        $awarded = awardLoyaltyPoints($userId, $points, 'earn', null, 'birthday', $description);
        
        if(!$awarded) {
            throw new Exception("Failed to award birthday points");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Redeem loyalty points for a discount
 * 
 * @param int $userId User ID
 * @param int $points Number of points to redeem
 * @return float|bool Discount amount if successful, false otherwise
 */
function redeemLoyaltyPoints($userId, $points) {
    global $conn;
    
    // Get user details
    $userQuery = "SELECT * FROM users WHERE id = $userId";
    $userResult = mysqli_query($conn, $userQuery);
    
    if(mysqli_num_rows($userResult) === 0) {
        return false; // User not found
    }
    
    $user = mysqli_fetch_assoc($userResult);
    
    // Check if user has enough points
    if($user['loyalty_points'] < $points) {
        return false; // Not enough points
    }
    
    // Get loyalty settings
    $settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings || !$settings['enabled']) {
        return false; // Loyalty program is disabled
    }
    
    // Check minimum redemption
    if($points < $settings['min_points_redemption']) {
        return false; // Below minimum redemption
    }
    
    // Calculate discount amount
    $discountAmount = $points * $settings['points_redemption_value'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Deduct points from user
        $updateQuery = "UPDATE users SET loyalty_points = loyalty_points - $points WHERE id = $userId";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if(!$updateResult) {
            throw new Exception("Failed to deduct loyalty points: " . mysqli_error($conn));
        }
        
        // Log the transaction
        $description = "Redeemed $points points for ₹" . number_format($discountAmount, 2) . " discount";
        $logQuery = "INSERT INTO loyalty_transactions (user_id, points, transaction_type, reference_type, description, created_at) 
                    VALUES ($userId, -$points, 'redeem', 'discount', '$description', NOW())";
        $logResult = mysqli_query($conn, $logQuery);
        
        if(!$logResult) {
            throw new Exception("Failed to log loyalty transaction: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        return $discountAmount;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get user's loyalty points and tier information
 * 
 * @param int $userId User ID
 * @return array|bool User's loyalty information if successful, false otherwise
 */
function getUserLoyaltyInfo($userId) {
    global $conn;
    
    // Get user details
    $userQuery = "SELECT u.*, cg.name as group_name, cg.discount_percentage 
                 FROM users u 
                 LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id 
                 WHERE u.id = $userId";
    $userResult = mysqli_query($conn, $userQuery);
    
    if(mysqli_num_rows($userResult) === 0) {
        return false; // User not found
    }
    
    $user = mysqli_fetch_assoc($userResult);
    
    // Get loyalty settings
    $settingsQuery = "SELECT * FROM loyalty_settings WHERE id = 1";
    $settingsResult = mysqli_query($conn, $settingsQuery);
    $settings = mysqli_fetch_assoc($settingsResult);
    
    if(!$settings) {
        return false; // Loyalty settings not found
    }
    
    // Get user's order stats
    $statsQuery = "SELECT 
                  COUNT(id) as order_count,
                  SUM(total_amount) as total_spent
                FROM orders 
                WHERE user_id = $userId";
    $statsResult = mysqli_query($conn, $statsQuery);
    $stats = mysqli_fetch_assoc($statsResult);
    
    // Get user's recent transactions
    $transactionsQuery = "SELECT * FROM loyalty_transactions 
                         WHERE user_id = $userId 
                         ORDER BY created_at DESC 
                         LIMIT 5";
    $transactionsResult = mysqli_query($conn, $transactionsQuery);
    $transactions = mysqli_fetch_all($transactionsResult, MYSQLI_ASSOC);
    
    // Calculate potential discount
    $potentialDiscount = 0;
    if($settings['enabled'] && $user['loyalty_points'] >= $settings['min_points_redemption']) {
        $potentialDiscount = $user['loyalty_points'] * $settings['points_redemption_value'];
    }
    
    // Return loyalty information
    return [
        'user_id' => $userId,
        'loyalty_points' => $user['loyalty_points'],
        'group_name' => $user['group_name'],
        'group_discount' => $user['discount_percentage'],
        'order_count' => $stats['order_count'],
        'total_spent' => $stats['total_spent'],
        'potential_discount' => $potentialDiscount,
        'min_points_redemption' => $settings['min_points_redemption'],
        'points_redemption_value' => $settings['points_redemption_value'],
        'program_enabled' => $settings['enabled'],
        'recent_transactions' => $transactions
    ];
}
