<?php
// Helper functions for the application

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function validate_phone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Generate a random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format currency
 */
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get user by email
 */
function get_user_by_email($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

/**
 * Get site settings
 */
function get_site_settings() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM site_settings WHERE id = 1");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Get site option
 */
function get_site_option($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT option_value FROM site_options WHERE option_name = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['option_value'] : $default;
}

/**
 * Update site option
 */
function update_site_option($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO site_options (option_name, option_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE option_value = ?
    ");
    $stmt->execute([$key, $value, $value]);
} 