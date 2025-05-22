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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Login to Your Account</h3>
                </div>
                <div class="card-body">
                    <div id="login-alert" class="alert alert-danger d-none"></div>
                    <form id="login-form" method="post">
                        <div class="form-group mb-3">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block">Login</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                    <hr>
                    <div class="text-center">
                        <p>Don't have an account? <a href="register.php">Register Now</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#login-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: 'ajax/process_login.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'index.php';
                } else {
                    $('#login-alert').removeClass('d-none').text(response.message);
                }
            },
            error: function() {
                $('#login-alert').removeClass('d-none').text('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
