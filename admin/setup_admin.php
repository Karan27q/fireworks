<?php
include '../includes/db_connect.php';

// Admin credentials
$email = 'admin@fireworks.com';
$password = 'Admin@123'; // Change this to your desired password
$name = 'Admin';

// Generate password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check_query = "SELECT id FROM admins WHERE email = '$email'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    // Update existing admin
    $admin = mysqli_fetch_assoc($check_result);
    $update_query = "UPDATE admins SET password = '$password_hash', name = '$name' WHERE id = {$admin['id']}";
    
    if(mysqli_query($conn, $update_query)) {
        echo "Admin password updated successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
    } else {
        echo "Error updating admin: " . mysqli_error($conn);
    }
} else {
    // Create new admin
    $insert_query = "INSERT INTO admins (email, password, name) VALUES ('$email', '$password_hash', '$name')";
    
    if(mysqli_query($conn, $insert_query)) {
        echo "Admin account created successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
    } else {
        echo "Error creating admin: " . mysqli_error($conn);
    }
}

// Show the password hash for verification
echo "<br>Password hash: $password_hash";
?> 