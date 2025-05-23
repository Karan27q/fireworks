<?php
require_once 'includes/db_connect.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Login";
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-wrapper">
        <div class="login-form-container">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Please login to your account</p>
            </div>
            
            <div id="login-alert" class="alert alert-danger d-none"></div>
            
            <form id="login-form" method="post" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-button">
                    <span>Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register Now</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
}

.login-wrapper {
    width: 100%;
    max-width: 450px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    padding: 40px;
}

.login-form-container {
    width: 100%;
}

.login-header {
    text-align: center;
    margin-bottom: 40px;
}

.login-header h2 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
}

.login-header p {
    color: #666;
    font-size: 1rem;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
}

.form-group label i {
    margin-right: 8px;
    color: #007bff;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    outline: none;
}

.password-input {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    cursor: pointer;
    transition: color 0.3s ease;
}

.toggle-password:hover {
    color: #007bff;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
}

.remember-me input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #007bff;
}

.forgot-password {
    color: #007bff;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.forgot-password:hover {
    color: #0056b3;
}

.login-button {
    width: 100%;
    padding: 14px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.login-button:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.login-button i {
    transition: transform 0.3s ease;
}

.login-button:hover i {
    transform: translateX(5px);
}

.register-link {
    text-align: center;
    margin-top: 25px;
    color: #666;
}

.register-link a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.register-link a:hover {
    color: #0056b3;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.alert-danger {
    background-color: #fff5f5;
    border: 1px solid #feb2b2;
    color: #c53030;
}

@media (max-width: 767px) {
    .login-wrapper {
        padding: 30px 20px;
    }
    
    .login-header h2 {
        font-size: 1.75rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').click(function() {
        const passwordInput = $('#password');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Form submission
    $('#login-form').submit(function(e) {
        e.preventDefault();
        
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        // Show loading state
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
        submitButton.prop('disabled', true);
        
        $.ajax({
            type: 'POST',
            url: 'ajax/process_login.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success state
                    submitButton.html('<i class="fas fa-check"></i> Success!');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    // Show error
                    $('#login-alert').removeClass('d-none').text(response.message);
                    submitButton.html(originalText);
                    submitButton.prop('disabled', false);
                }
            },
            error: function() {
                $('#login-alert').removeClass('d-none').text('An error occurred. Please try again.');
                submitButton.html(originalText);
                submitButton.prop('disabled', false);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
