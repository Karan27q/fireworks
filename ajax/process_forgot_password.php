<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// Validate input
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode(['success' => true, 'message' => 'If your email is registered, you will receive a password reset link shortly.']);
        exit;
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour
    
    // Store token in database
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user['id']]);
    
    // In a real application, send email with reset link
    // $reset_link = "https://yourwebsite.com/reset-password.php?token=$token";
    // sendResetEmail($email, $reset_link);
    
    // For demo purposes, just return success
    echo json_encode(['success' => true, 'message' => 'If your email is registered, you will receive a password reset link shortly.']);
    
} catch (PDOException $e) {
    error_log("Forgot password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
