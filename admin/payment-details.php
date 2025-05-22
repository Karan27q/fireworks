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

// Get current payment details content
$paymentQuery = "SELECT * FROM pages WHERE slug = 'payment-details'";
$paymentResult = mysqli_query($conn, $paymentQuery);

if(mysqli_num_rows($paymentResult) === 0) {
    // Create default payment details page if it doesn't exist
    $defaultContent = '<h1>Payment Details</h1>
<p>We offer multiple payment methods to make your shopping experience convenient and secure.</p>

<h2>Payment Methods</h2>
<p>We accept the following payment methods:</p>
<ul>
<li><strong>Cash on Delivery (COD):</strong> Pay in cash when your order is delivered to your doorstep.</li>
<li><strong>Online Payment:</strong> Pay securely online using credit/debit cards, net banking, UPI, or digital wallets.</li>
<li><strong>Google Pay:</strong> Make quick and secure payments using Google Pay.</li>
</ul>

<h2>Payment Security</h2>
<p>All online payments are processed through secure payment gateways. Your payment information is encrypted and protected.</p>

<h2>GST Information</h2>
<p>GST is included in the product prices displayed on our website. Tax invoices will be provided with your order.</p>

<h2>Refund Policy</h2>
<p>Refunds will be processed through the original payment method used for the purchase. Please refer to our refund policy for more details.</p>

<h2>Bank Account Details</h2>
<p>For direct bank transfers, please use the following details:</p>
<ul>
<li><strong>Bank Name:</strong> [Bank Name]</li>
<li><strong>Account Name:</strong> [Account Name]</li>
<li><strong>Account Number:</strong> [Account Number]</li>
<li><strong>IFSC Code:</strong> [IFSC Code]</li>
<li><strong>Branch:</strong> [Branch Name]</li>
</ul>
<p>Please note that orders paid through bank transfer will be processed only after payment confirmation.</p>

<h2>Contact Us</h2>
<p>If you have any questions about our payment methods or encounter any issues while making a payment, please contact our customer service team.</p>';
    
    $insertQuery = "INSERT INTO pages (title, slug, content, meta_title, meta_description, active, created_at) 
                   VALUES ('Payment Details', 'payment-details', '$defaultContent', 'Payment Details - Fireworks Shop', 'Learn about our payment methods, security, and refund policies.', 1, NOW())";
    mysqli_query($conn, $insertQuery);
    
    // Get the newly created page
    $paymentResult = mysqli_query($conn, $paymentQuery);
}

$paymentPage = mysqli_fetch_assoc($paymentResult);

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
                   WHERE slug = 'payment-details'";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Payment Details page updated successfully";
        
        // Refresh page data
        $paymentResult = mysqli_query($conn, $paymentQuery);
        $paymentPage = mysqli_fetch_assoc($paymentResult);
    } else {
        $errorMessage = "Failed to update Payment Details page: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment Details - Admin Panel</title>
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
            
            <!-- Payment Details Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Edit Payment Details</h1>
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
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo $paymentPage['title']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Page Content</label>
                                <textarea id="content" name="content" class="form-control"><?php echo $paymentPage['content']; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo $paymentPage['meta_title']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo $paymentPage['meta_description']; ?></textarea>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="active" name="active" <?php echo $paymentPage['active'] ? 'checked' : ''; ?>>
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
