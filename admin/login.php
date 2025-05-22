<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If already logged in, redirect to dashboard
if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

$error = '';

// Handle login form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if(empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check if admin exists
        $query = "SELECT * FROM admins WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if(!$result) {
            $error = 'Database error: ' . mysqli_error($conn);
        } else if(mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);
            
            // Debug information
            echo "<!-- Debug Info:
            Email: $email
            Stored Hash: {$admin['password']}
            Verification Result: " . (password_verify($password, $admin['password']) ? 'true' : 'false') . "
            -->";
            
            // Verify password
            if(password_verify($password, $admin['password'])) {
                // Set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                
                // Log activity
                $activityQuery = "INSERT INTO activity_log (user_id, user_type, action, ip_address) 
                                 VALUES ({$admin['id']}, 'admin', 'login', '{$_SERVER['REMOTE_ADDR']}')";
                mysqli_query($conn, $activityQuery);
                
                // Redirect to dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Admin not found';
        }
    }
}

// Get site settings for logo
$settingsQuery = "SELECT site_name, logo FROM site_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo $settings['site_name']; ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            max-width: 150px;
            height: auto;
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .login-form .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .login-form .form-control:focus {
            border-color: #4caf50;
            outline: none;
        }
        
        .login-form .btn {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <?php if($settings['logo']): ?>
                <img src="../uploads/logo/<?php echo $settings['logo']; ?>" alt="<?php echo $settings['site_name']; ?>">
            <?php else: ?>
                <h2><?php echo $settings['site_name']; ?></h2>
            <?php endif; ?>
        </div>
        
        <h1 class="login-title">Admin Login</h1>
        
        <?php if($error): ?>
            <div class="login-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $settings['site_name']; ?>. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
