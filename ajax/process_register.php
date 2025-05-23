<?php
// Prevent any output before JSON response
ob_start();

// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the start of the registration process
error_log("Starting registration process");

// Include required files
$required_files = [
    '../includes/db_connect.php',
    '../includes/functions.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        error_log("Required file not found: $file");
        echo json_encode(['success' => false, 'message' => 'System configuration error']);
        exit;
    }
    require_once $file;
}

// Ensure we're sending JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Log POST data
    error_log("Registration POST data: " . print_r($_POST, true));

    // Get and sanitize input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;

    // Log sanitized data
    error_log("Sanitized data - Name: $name, Email: $email, Phone: $phone");

    // Validate input
    if (!$name || !$email || !$phone || !$password || !$confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }

    if (!$terms) {
        echo json_encode(['success' => false, 'message' => 'Please accept the terms and conditions']);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email address is already registered']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, status, created_at) 
        VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([$name, $email, $phone, $hashed_password]);
    $user_id = $pdo->lastInsertId();
    
    error_log("User registered successfully with ID: $user_id");
    
    // Add welcome loyalty points if enabled
    $stmt = $pdo->prepare("SELECT welcome_points, enabled FROM loyalty_settings WHERE id = 1");
    $stmt->execute();
    $loyalty_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loyalty_settings && $loyalty_settings['enabled'] && $loyalty_settings['welcome_points'] > 0) {
        // Add points to user
        $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
        $stmt->execute([$loyalty_settings['welcome_points'], $user_id]);
        
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_transactions (user_id, points, transaction_type, description, created_at)
            VALUES (?, ?, 'earn', 'Welcome bonus points', NOW())
        ");
        $stmt->execute([$user_id, $loyalty_settings['welcome_points']]);
        
        error_log("Added welcome points to user ID: $user_id");
    }
    
    // Clear any output buffers
    ob_end_clean();
    
    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);
    
} catch (PDOException $e) {
    error_log("Registration PDO error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
} catch (Exception $e) {
    error_log("Registration general error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
