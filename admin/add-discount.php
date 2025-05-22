<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $discountType = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discountValue = (float)$_POST['discount_value'];
    $minOrderAmount = (float)$_POST['min_order_amount'];
    $maxDiscountAmount = (float)$_POST['max_discount_amount'];
    $usageLimit = (int)$_POST['usage_limit'];
    $userUsageLimit = (int)$_POST['user_usage_limit'];
    $startDate = mysqli_real_escape_string($conn, $_POST['start_date']);
    $endDate = mysqli_real_escape_string($conn, $_POST['end_date']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate discount code (unique)
    $checkCodeQuery = "SELECT id FROM discounts WHERE code = '$code'";
    $checkCodeResult = mysqli_query($conn, $checkCodeQuery);
    
    if(mysqli_num_rows($checkCodeResult) > 0) {
        $errorMessage = "Discount code already exists. Please use a different code.";
    } else {
        // Insert discount
        $insertQuery = "INSERT INTO discounts (
                            name, 
                            code, 
                            description, 
                            discount_type, 
                            discount_value, 
                            min_order_amount, 
                            max_discount_amount, 
                            usage_limit, 
                            user_usage_limit, 
                            start_date, 
                            end_date, 
                            active
                        ) VALUES (
                            '$name', 
                            '$code', 
                            '$description', 
                            '$discountType', 
                            $discountValue, 
                            $minOrderAmount, 
                            $maxDiscountAmount, 
                            $usageLimit, 
                            $userUsageLimit, 
                            '$startDate', 
                            '$endDate', 
                            $active
                        )";
        
        $insertResult = mysqli_query($conn, $insertQuery);
        
        if($insertResult) {
            // Redirect to discounts page
            header('Location: discounts.php?success=1');
            exit;
        } else {
            $errorMessage = "Failed to add discount: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Discount - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Add Discount Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Add New Discount</h1>
                    <a href="discounts.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Discounts
                    </a>
                </div>
                
                <?php if(isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name">Discount Name <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" required>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="code">Discount Code <span class="required">*</span></label>
                                    <input type="text" id="code" name="code" class="form-control" required>
                                    <small class="form-text">Unique code for the discount (e.g., SUMMER20)</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="discount_type">Discount Type <span class="required">*</span></label>
                                    <select id="discount_type" name="discount_type" class="form-control" required>
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="discount_value">Discount Value <span class="required">*</span></label>
                                    <input type="number" id="discount_value" name="discount_value" class="form-control" min="0" step="0.01" required>
                                    <small class="form-text discount-type-hint">Percentage off the order total</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="min_order_amount">Minimum Order Amount</label>
                                    <input type="number" id="min_order_amount" name="min_order_amount" class="form-control" min="0" step="0.01" value="0">
                                    <small class="form-text">Minimum order amount required to use this discount</small>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="max_discount_amount">Maximum Discount Amount</label>
                                    <input type="number" id="max_discount_amount" name="max_discount_amount" class="form-control" min="0" step="0.01" value="0">
                                    <small class="form-text">Maximum discount amount (0 for no limit)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="usage_limit">Total Usage Limit</label>
                                    <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="0" value="0">
                                    <small class="form-text">Maximum number of times this discount can be used (0 for unlimited)</small>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="user_usage_limit">Per User Usage Limit</label>
                                    <input type="number" id="user_usage_limit" name="user_usage_limit" class="form-control" min="0" value="1">
                                    <small class="form-text">Maximum number of times a user can use this discount</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="start_date">Start Date <span class="required">*</span></label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="end_date">End Date <span class="required">*</span></label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="active" name="active" checked>
                                <label for="active">Active</label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Add Discount</button>
                                <a href="discounts.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const discountTypeSelect = document.getElementById('discount_type');
            const discountTypeHint = document.querySelector('.discount-type-hint');
            
            // Update hint based on discount type
            function updateDiscountTypeHint() {
                if(discountTypeSelect.value === 'percentage') {
                    discountTypeHint.textContent = 'Percentage off the order total';
                } else {
                    discountTypeHint.textContent = 'Fixed amount off the order total';
                }
            }
            
            discountTypeSelect.addEventListener('change', updateDiscountTypeHint);
            
            // Set min date for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').setAttribute('min', today);
            document.getElementById('end_date').setAttribute('min', today);
            
            // Set default dates
            document.getElementById('start_date').value = today;
            
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            document.getElementById('end_date').value = nextMonth.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
