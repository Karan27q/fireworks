<?php
require_once 'includes/db_connect.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Register";
include 'includes/header.php';
?>

<div class="register-container">
    <div class="register-wrapper">
        <div class="register-form-container">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Join us and start shopping</p>
            </div>
            
            <div id="register-alert" class="alert alert-danger d-none"></div>
            
            <form id="register-form" method="post" class="register-form">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                        <small class="password-hint">Password must be at least 8 characters long and include a number and a special character.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group terms-group">
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="register-button">
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.register-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
}

.register-wrapper {
    width: 100%;
    max-width: 600px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    padding: 40px;
}

.register-form-container {
    width: 100%;
}

.register-header {
    text-align: center;
    margin-bottom: 40px;
}

.register-header h2 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
}

.register-header p {
    color: #666;
    font-size: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.password-hint {
    display: block;
    margin-top: 8px;
    color: #666;
    font-size: 0.85rem;
}

.terms-group {
    margin: 30px 0;
}

.terms-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
}

.terms-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #007bff;
}

.terms-checkbox label {
    margin: 0;
    font-size: 0.95rem;
}

.terms-checkbox a {
    color: #007bff;
    text-decoration: none;
    transition: color 0.3s ease;
}

.terms-checkbox a:hover {
    color: #0056b3;
}

.register-button {
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

.register-button:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.register-button i {
    transition: transform 0.3s ease;
}

.register-button:hover i {
    transform: translateX(5px);
}

.login-link {
    text-align: center;
    margin-top: 25px;
    color: #666;
}

.login-link a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.login-link a:hover {
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
    .register-wrapper {
        padding: 30px 20px;
    }
    
    .register-header h2 {
        font-size: 1.75rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').click(function() {
        const passwordInput = $(this).siblings('input');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Form submission
    $('#register-form').submit(function(e) {
        e.preventDefault();
        
        console.log('Form submitted');
        console.log('Form data:', $(this).serialize());
        
        // Basic validation
        if ($('#password').val() !== $('#confirm_password').val()) {
            $('#register-alert').removeClass('d-none').text('Passwords do not match.');
            return;
        }
        
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        // Show loading state
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Creating Account...');
        submitButton.prop('disabled', true);
        
        $.ajax({
            type: 'POST',
            url: 'ajax/process_register.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Registration response:', response);
                if (response.success) {
                    // Show success state
                    submitButton.html('<i class="fas fa-check"></i> Account Created!');
                    setTimeout(function() {
                        window.location.href = 'login.php?registered=1';
                    }, 1000);
                } else {
                    // Show error
                    $('#register-alert').removeClass('d-none').text(response.message);
                    submitButton.html(originalText);
                    submitButton.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Registration error:', {xhr, status, error});
                $('#register-alert').removeClass('d-none').text('An error occurred. Please try again.');
                submitButton.html(originalText);
                submitButton.prop('disabled', false);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
