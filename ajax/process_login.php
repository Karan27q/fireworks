<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both email and password']);
    exit;
}

// Sanitize email
$email = mysqli_real_escape_string($conn, $email);

// Get user from database
$query = "SELECT id, email, password, name FROM users WHERE email = '$email' AND active = 1 LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Verify password
if (password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $query = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES ({$user['id']}, '$token', FROM_UNIXTIME($expires))";
        mysqli_query($conn, $query);
        
        // Set cookie
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }
    
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
}
