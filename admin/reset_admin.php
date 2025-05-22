<?php
include '../includes/db_connect.php';

// Admin credentials
$email = 'admin@fireworks.com';
$password = 'Admin@123';
$name = 'Admin';

// Generate new password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Update admin password
$update_query = "UPDATE admins SET password = '$password_hash' WHERE email = '$email'";

if(mysqli_query($conn, $update_query)) {
    echo "Admin password has been reset successfully!<br>";
    echo "Email: $email<br>";
    echo "New Password: $password<br>";
    echo "New Hash: $password_hash<br>";
    
    // Verify the new hash
    echo "<br>Verification Test:<br>";
    echo "Password verification result: " . (password_verify($password, $password_hash) ? 'TRUE' : 'FALSE');
} else {
    echo "Error updating password: " . mysqli_error($conn);
}
?> 