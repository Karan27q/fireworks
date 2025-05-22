<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if token is valid
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE reset_token = ? 
            AND reset_token_expires > NOW() 
            AND status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $valid_token = true;
            $user_id = $user['id'];
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
    }
}

$page_title = "Reset Password";
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
                    <?php if (!$valid_token): ?>
                        <div class="alert alert-danger">
                            Invalid or expired reset token. Please request a new password reset link.
                        </div>
                        <div class="text-center mt-3">
                            <a href="forgot-password.php" class="btn btn-primary">Request New Link</a>
                        </div>
                    <?php else: ?>
                        <div id="reset-alert" class="alert d-none"></div>
                        <form id="reset-password-form" method="post">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            
                            <div class="form-group mb-3">
                                <label for="password">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Password must be at least 8 characters long and include a number and a special character.</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($valid_token): ?>
<script>
$(document).ready(function() {
    $('#reset-password-form').submit(function(e) {
        e.preventDefault();
        
        // Basic validation
        if ($('#password').val() !== $('#confirm_password').val()) {
            $('#reset-alert').removeClass('d-none').addClass('alert-danger').text('Passwords do not match.');
            return;
        }
        
        if ($('#password').val().length < 8) {
            $('#reset-alert').removeClass('d-none').addClass('alert-danger').text('Password must be at least 8 characters long.');
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: 'ajax/process_reset_password.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#reset-alert').removeClass('d-none alert-danger alert-success');
                if (response.success) {
                    $('#reset-alert').addClass('alert-success').text(response.message);
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
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
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
