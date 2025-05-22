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

// Get current about us content
$aboutQuery = "SELECT * FROM pages WHERE slug = 'about-us'";
$aboutResult = mysqli_query($conn, $aboutQuery);

if(mysqli_num_rows($aboutResult) === 0) {
    // Create default about us page if it doesn't exist
    $defaultContent = '<h1>About Us</h1>
<p>Welcome to our fireworks shop! We are a leading provider of high-quality fireworks for all occasions.</p>
<p>Our mission is to bring joy and excitement to your celebrations with safe and spectacular fireworks products.</p>
<h2>Our Story</h2>
<p>Established in 2010, we have been serving customers across India with premium fireworks products. Our journey began with a small shop and has now grown into a trusted name in the industry.</p>
<h2>Why Choose Us?</h2>
<ul>
<li>Wide range of high-quality fireworks</li>
<li>Competitive prices</li>
<li>Fast and reliable shipping</li>
<li>Excellent customer service</li>
<li>Safe and tested products</li>
</ul>
<p>We are committed to providing you with the best fireworks shopping experience.</p>';
    
    $insertQuery = "INSERT INTO pages (title, slug, content, meta_title, meta_description, active, created_at) 
                   VALUES ('About Us', 'about-us', '$defaultContent', 'About Us - Fireworks Shop', 'Learn more about our fireworks shop and our commitment to quality and safety.', 1, NOW())";
    mysqli_query($conn, $insertQuery);
    
    // Get the newly created page
    $aboutResult = mysqli_query($conn, $aboutQuery);
}

$aboutPage = mysqli_fetch_assoc($aboutResult);

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
                   WHERE slug = 'about-us'";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "About Us page updated successfully";
        
        // Refresh page data
        $aboutResult = mysqli_query($conn, $aboutQuery);
        $aboutPage = mysqli_fetch_assoc($aboutResult);
    } else {
        $errorMessage = "Failed to update About Us page: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit About Us Page - Admin Panel</title>
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
            
            <!-- About Us Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Edit About Us Page</h1>
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
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo $aboutPage['title']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Page Content</label>
                                <textarea id="content" name="content" class="form-control"><?php echo $aboutPage['content']; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo $aboutPage['meta_title']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo $aboutPage['meta_description']; ?></textarea>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="active" name="active" <?php echo $aboutPage['active'] ? 'checked' : ''; ?>>
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
