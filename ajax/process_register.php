<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input
$first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
$last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']) ? true : false;

// Validate input
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (!$terms) {
    echo json_encode(['success' => false, 'message' => 'You must agree to the terms and conditions']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email address is already registered']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password, verification_token, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $verification_token]);
    $user_id = $pdo->lastInsertId();
    
    // Create customer record
    $stmt = $pdo->prepare("
        INSERT INTO customers (user_id, first_name, last_name, email, phone, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$user_id, $first_name, $last_name, $email, $phone]);
    
    // Send verification email (in a real application)
    // sendVerificationEmail($email, $verification_token);
    
    // For demo purposes, auto-verify the account
    $stmt = $pdo->prepare("UPDATE users SET status = 'active', email_verified_at = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
