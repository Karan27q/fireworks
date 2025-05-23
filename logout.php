<?php
require_once 'includes/db_connect.php';
session_start();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete token from database
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
    $stmt->bind_param("s", $_COOKIE['remember_token']);
    $stmt->execute();
    
    // Remove cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Clear session
session_unset();
session_destroy();

// Set flash message
session_start();
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to login page
header('Location: login.php');
exit; 