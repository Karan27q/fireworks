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

// Get current shipping policy content
$policyQuery = "SELECT * FROM pages WHERE slug = 'shipping-policy'";
$policyResult = mysqli_query($conn, $policyQuery);

if(mysqli_num_rows($policyResult) === 0) {
    // Create default shipping policy page if it doesn't exist
    $defaultContent = '<h1>Shipping Policy</h1>
<p>Thank you for visiting our website. This shipping policy outlines our procedures and policies related to the shipping of products ordered through our website.</p>

<h2>Shipping Methods and Timeframes</h2>
<p>We offer shipping throughout India, including North Eastern States. Our standard shipping method is through trusted courier partners.</p>
<ul>
<li>Standard Shipping: 3-5 business days</li>
<li>Express Shipping: 1-2 business days (available for select locations)</li>
</ul>

<h2>Shipping Costs</h2>
<p>Shipping costs are calculated based on the delivery location and the weight of the package. The exact shipping cost will be displayed during checkout before payment is made.</p>

<h2>Minimum Order Value</h2>
<p>Please note that we have a minimum order value of INR 2500. Orders below this amount will not be processed.</p>

<h2>Order Processing Time</h2>
<p>Orders are typically processed within 24-48 hours of being placed. During peak seasons (like Diwali), processing times may be slightly longer.</p>

<h2>Tracking Your Order</h2>
<p>Once your order is shipped, you will receive a tracking number via email. You can use this tracking number to monitor the status of your delivery.</p>

<h2>Delivery Issues</h2>
<p>If you encounter any issues with your delivery, please contact our customer service team immediately. We will work with our shipping partners to resolve any problems.</p>

<h2>Shipping Restrictions</h2>
<p>Due to the nature of our products, there may be certain areas where we cannot ship. Please check with our customer service team if you are unsure about delivery to your location.</p>

<h2>Contact Us</h2>
<p>If you have any questions about our shipping policy, please contact us through our customer service channels.</p>';
    
    $insertQuery = "INSERT INTO pages (title, slug, content, meta_title, meta_description, active, created_at) 
                   VALUES ('Shipping Policy', 'shipping-policy', '$defaultContent', 'Shipping Policy - Fireworks Shop', 'Learn about our shipping policies, delivery timeframes, and costs.', 1, NOW())";
    mysqli_query($conn, $insertQuery);
    
    // Get the newly created page
    $policyResult = mysqli_query($conn, $policyQuery);
}

$policyPage = mysqli_fetch_assoc($policyResult);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $metaTitle = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $metaDescription = mysqli_real_escape_string($conn, $_POST['meta_description']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    $updateQuery = "UPDATE pages SET 
                   title = '$title',
                   content = '$content',
                   meta_title = '$metaTitle',
                   meta_description = '$metaDescription',
                   active = $active,
                   updated_at = NOW()
                   WHERE slug = 'shipping-policy'";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Shipping Policy page updated successfully";
        
        // Refresh page data
        $policyResult = mysqli_query($conn, $policyQuery);
        $policyPage = mysqli_fetch_assoc($policyResult);
    } else {
        $errorMessage = "Failed to update Shipping Policy page: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Shipping Policy - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 500,
            plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
            toolbar_mode: 'floating',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
        });
    </script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Shipping Policy Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Edit Shipping Policy</h1>
                </div>
                
                <?php if(isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="title">Page Title</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo $policyPage['title']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Page Content</label>
                                <textarea id="content" name="content" class="form-control"><?php echo $policyPage['content']; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo $policyPage['meta_title']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo $policyPage['meta_description']; ?></textarea>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="active" name="active" <?php echo $policyPage['active'] ? 'checked' : ''; ?>>
                                <label for="active">Active</label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
