<?php
session_start();

// Include database connection
include '../includes/db_connect.php';

// Log the logout activity if admin is logged in
if(isset($_SESSION['admin_id'])) {
    $adminId = $_SESSION['admin_id'];
    $activityQuery = "INSERT INTO activity_log (user_id, user_type, action, ip_address) 
                     VALUES ($adminId, 'admin', 'logout', '{$_SERVER['REMOTE_ADDR']}')";
    mysqli_query($conn, $activityQuery);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?> 