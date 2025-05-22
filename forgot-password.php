<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Forgot Password";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Reset Your Password</h3>
                </div>
                <div class="card-body">
                    <div id="reset-alert" class="alert d-none"></div>
                    <p class="mb-4">Enter your email address and we'll send you a link to reset your password.</p>
                    <form id="forgot-password-form" method="post">
                        <div class="form-group mb-3">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#forgot-password-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: 'ajax/process_forgot_password.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#reset-alert').removeClass('d-none alert-danger alert-success');
                if (response.success) {
                    $('#reset-alert').addClass('alert-success').text(response.message);
                    $('#forgot-password-form')[0].reset();
                } else {
                    $('#reset-alert').addClass('alert-danger').text(response.message);
                }
            },
            error: function() {
                $('#reset-alert').removeClass('d-none').addClass('alert-danger').text('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
