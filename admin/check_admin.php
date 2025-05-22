<?php
include '../includes/db_connect.php';

// Check admin account
$email = 'admin@fireworks.com';
$query = "SELECT * FROM admins WHERE email = '$email'";
$result = mysqli_query($conn, $query);

echo "<h2>Admin Account Check</h2>";

if(mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_assoc($result);
    echo "Admin account found:<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Name: " . $admin['name'] . "<br>";
    echo "Password Hash: " . $admin['password'] . "<br>";
    
    // Test password verification
    $test_password = 'Admin@123';
    echo "<br>Testing password verification:<br>";
    echo "Test password: " . $test_password . "<br>";
    echo "Verification result: " . (password_verify($test_password, $admin['password']) ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "No admin account found with email: $email";
}

// Check database connection
echo "<br><br>Database Connection Test:<br>";
if($conn) {
    echo "Database connection successful<br>";
    echo "Server info: " . mysqli_get_server_info($conn);
} else {
    echo "Database connection failed";
}
?> 