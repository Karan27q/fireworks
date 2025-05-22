<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input
$token = $_POST['token'] ?? '';
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($token) || empty($user_id) || empty($password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    // Verify token is valid
    $stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND reset_token = ? 
        AND reset_token_expires > NOW() 
        AND status = 'active'
    ");
    $stmt->execute([$user_id, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit;
    }
    
    // Hash new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password and clear reset token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, reset_token = NULL, reset_token_expires = NULL, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$hashed_password, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Password has been reset successfully. You can now login with your new password.']);
    
} catch (PDOException $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
