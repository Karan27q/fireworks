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

// Get current settings
$settingsQuery = "SELECT * FROM site_settings WHERE id = 1";
$settingsResult = mysqli_query($conn, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Settings
    $siteName = mysqli_real_escape_string($conn, $_POST['site_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $whatsapp = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Social Media
    $socialFacebook = mysqli_real_escape_string($conn, $_POST['social_facebook']);
    $socialFacebookUrl = mysqli_real_escape_string($conn, $_POST['social_facebook_url']);
    $socialInstagram = mysqli_real_escape_string($conn, $_POST['social_instagram']);
    $socialInstagramUrl = mysqli_real_escape_string($conn, $_POST['social_instagram_url']);
    $socialTwitter = mysqli_real_escape_string($conn, $_POST['social_twitter']);
    $socialTwitterUrl = mysqli_real_escape_string($conn, $_POST['social_twitter_url']);
    $socialYoutube = mysqli_real_escape_string($conn, $_POST['social_youtube']);
    $socialYoutubeUrl = mysqli_real_escape_string($conn, $_POST['social_youtube_url']);
    
    // Business Settings
    $licenseText = mysqli_real_escape_string($conn, $_POST['license_text']);
    $minOrderAmount = mysqli_real_escape_string($conn, $_POST['min_order_amount']);
    $shippingFee = mysqli_real_escape_string($conn, $_POST['shipping_fee']);
    $taxRate = mysqli_real_escape_string($conn, $_POST['tax_rate']);
    
    // Features
    $b2bEnabled = isset($_POST['b2b_enabled']) ? 1 : 0;
    $chatEnabled = isset($_POST['chat_enabled']) ? 1 : 0;
    $reviewsEnabled = isset($_POST['reviews_enabled']) ? 1 : 0;
    $wishlistEnabled = isset($_POST['wishlist_enabled']) ? 1 : 0;
    $compareEnabled = isset($_POST['compare_enabled']) ? 1 : 0;
    
    // SEO Settings
    $metaTitle = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $metaDescription = mysqli_real_escape_string($conn, $_POST['meta_description']);
    $metaKeywords = mysqli_real_escape_string($conn, $_POST['meta_keywords']);
    
    // Analytics
    $googleAnalyticsId = mysqli_real_escape_string($conn, $_POST['google_analytics_id']);
    $facebookPixelId = mysqli_real_escape_string($conn, $_POST['facebook_pixel_id']);
    
    // Payment Settings
    $codEnabled = isset($_POST['cod_enabled']) ? 1 : 0;
    $onlinePaymentEnabled = isset($_POST['online_payment_enabled']) ? 1 : 0;
    $razorpayKeyId = mysqli_real_escape_string($conn, $_POST['razorpay_key_id']);
    $razorpayKeySecret = mysqli_real_escape_string($conn, $_POST['razorpay_key_secret']);
    
    // Handle logo upload
    $logoPath = $settings['logo'];
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['logo']['type'];
        
        if(in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . $_FILES['logo']['name'];
            $uploadPath = '../uploads/logo/' . $fileName;
            
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                // Delete old logo if exists
                if($settings['logo'] && file_exists('../uploads/logo/' . $settings['logo'])) {
                    unlink('../uploads/logo/' . $settings['logo']);
                }
                
                $logoPath = $fileName;
            }
        }
    }
    
    // Handle favicon upload
    $faviconPath = $settings['favicon'];
    if(isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/x-icon'];
        $fileType = $_FILES['favicon']['type'];
        
        if(in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . $_FILES['favicon']['name'];
            $uploadPath = '../uploads/logo/' . $fileName;
            
            if(move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadPath)) {
                // Delete old favicon if exists
                if($settings['favicon'] && file_exists('../uploads/logo/' . $settings['favicon'])) {
                    unlink('../uploads/logo/' . $settings['favicon']);
                }
                
                $faviconPath = $fileName;
            }
        }
    }
    
    // Update settings
    $updateQuery = "UPDATE site_settings SET 
                    site_name = '$siteName',
                    logo = '$logoPath',
                    favicon = '$faviconPath',
                    email = '$email',
                    phone = '$phone',
                    whatsapp = '$whatsapp',
                    address = '$address',
                    location = '$location',
                    social_facebook = '$socialFacebook',
                    social_facebook_url = '$socialFacebookUrl',
                    social_instagram = '$socialInstagram',
                    social_instagram_url = '$socialInstagramUrl',
                    social_twitter = '$socialTwitter',
                    social_twitter_url = '$socialTwitterUrl',
                    social_youtube = '$socialYoutube',
                    social_youtube_url = '$socialYoutubeUrl',
                    license_text = '$licenseText',
                    min_order_amount = '$minOrderAmount',
                    shipping_fee = '$shippingFee',
                    tax_rate = '$taxRate',
                    b2b_enabled = $b2bEnabled,
                    chat_enabled = $chatEnabled,
                    reviews_enabled = $reviewsEnabled,
                    wishlist_enabled = $wishlistEnabled,
                    compare_enabled = $compareEnabled,
                    meta_title = '$metaTitle',
                    meta_description = '$metaDescription',
                    meta_keywords = '$metaKeywords',
                    google_analytics_id = '$googleAnalyticsId',
                    facebook_pixel_id = '$facebookPixelId',
                    cod_enabled = $codEnabled,
                    online_payment_enabled = $onlinePaymentEnabled,
                    razorpay_key_id = '$razorpayKeyId',
                    razorpay_key_secret = '$razorpayKeySecret'
                    WHERE id = 1";
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if($updateResult) {
        $successMessage = "Settings updated successfully";
        
        // Refresh settings
        $settingsResult = mysqli_query($conn, $settingsQuery);
        $settings = mysqli_fetch_assoc($settingsResult);
    } else {
        $errorMessage = "Failed to update settings: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
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
            
            <!-- Settings Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <h1>Site Settings</h1>
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
                
                <div class="settings-container">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Settings Tabs -->
                        <div class="settings-tabs">
                            <div class="tab-nav">
                                <button type="button" class="tab-btn active" data-tab="general">General</button>
                                <button type="button" class="tab-btn" data-tab="contact">Contact & Social</button>
                                <button type="button" class="tab-btn" data-tab="business">Business</button>
                                <button type="button" class="tab-btn" data-tab="features">Features</button>
                                <button type="button" class="tab-btn" data-tab="seo">SEO & Analytics</button>
                                <button type="button" class="tab-btn" data-tab="payment">Payment</button>
                            </div>
                            
                            <!-- General Settings -->
                            <div class="tab-content active" id="general">
                                <div class="form-group">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo $settings['site_name']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="logo">Logo</label>
                                    <div class="file-upload">
                                        <input type="file" id="logo" name="logo" class="file-input">
                                        <label for="logo" class="file-label">Choose File</label>
                                        <span class="file-name">No file chosen</span>
                                    </div>
                                    <?php if($settings['logo']): ?>
                                        <div class="current-image">
                                            <img src="../uploads/logo/<?php echo $settings['logo']; ?>" alt="Current Logo" width="150">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="favicon">Favicon</label>
                                    <div class="file-upload">
                                        <input type="file" id="favicon" name="favicon" class="file-input">
                                        <label for="favicon" class="file-label">Choose File</label>
                                        <span class="file-name">No file chosen</span>
                                    </div>
                                    <?php if($settings['favicon']): ?>
                                        <div class="current-image">
                                            <img src="../uploads/logo/<?php echo $settings['favicon']; ?>" alt="Current Favicon" width="32">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Contact & Social Settings -->
                            <div class="tab-content" id="contact">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo $settings['email']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $settings['phone']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="whatsapp">WhatsApp</label>
                                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?php echo $settings['whatsapp']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo $settings['address']; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="location">Location (City, State)</label>
                                    <input type="text" id="location" name="location" class="form-control" value="<?php echo $settings['location']; ?>">
                                </div>
                                
                                <h3 class="settings-subtitle">Social Media</h3>
                                
                                <div class="form-group">
                                    <label for="social_facebook">Facebook Username</label>
                                    <input type="text" id="social_facebook" name="social_facebook" class="form-control" value="<?php echo $settings['social_facebook']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_facebook_url">Facebook URL</label>
                                    <input type="url" id="social_facebook_url" name="social_facebook_url" class="form-control" value="<?php echo $settings['social_facebook_url']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_instagram">Instagram Username</label>
                                    <input type="text" id="social_instagram" name="social_instagram" class="form-control" value="<?php echo $settings['social_instagram']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_instagram_url">Instagram URL</label>
                                    <input type="url" id="social_instagram_url" name="social_instagram_url" class="form-control" value="<?php echo $settings['social_instagram_url']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_twitter">Twitter Username</label>
                                    <input type="text" id="social_twitter" name="social_twitter" class="form-control" value="<?php echo $settings['social_twitter']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_twitter_url">Twitter URL</label>
                                    <input type="url" id="social_twitter_url" name="social_twitter_url" class="form-control" value="<?php echo $settings['social_twitter_url']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_youtube">YouTube Username</label>
                                    <input type="text" id="social_youtube" name="social_youtube" class="form-control" value="<?php echo $settings['social_youtube']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="social_youtube_url">YouTube URL</label>
                                    <input type="url" id="social_youtube_url" name="social_youtube_url" class="form-control" value="<?php echo $settings['social_youtube_url']; ?>">
                                </div>
                            </div>
                            
                            <!-- Business Settings -->
                            <div class="tab-content" id="business">
                                <div class="form-group">
                                    <label for="license_text">License Text</label>
                                    <textarea id="license_text" name="license_text" class="form-control" rows="3"><?php echo $settings['license_text']; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="min_order_amount">Minimum Order Amount (₹)</label>
                                    <input type="number" id="min_order_amount" name="min_order_amount" class="form-control" value="<?php echo $settings['min_order_amount'] ?? 2500; ?>" min="0" step="100">
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping_fee">Default Shipping Fee (₹)</label>
                                    <input type="number" id="shipping_fee" name="shipping_fee" class="form-control" value="<?php echo $settings['shipping_fee'] ?? 0; ?>" min="0" step="10">
                                </div>
                                
                                <div class="form-group">
                                    <label for="tax_rate">Tax Rate (%)</label>
                                    <input type="number" id="tax_rate" name="tax_rate" class="form-control" value="<?php echo $settings['tax_rate'] ?? 18; ?>" min="0" max="100" step="0.01">
                                </div>
                            </div>
                            
                            <!-- Features Settings -->
                            <div class="tab-content" id="features">
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="b2b_enabled" name="b2b_enabled" <?php echo $settings['b2b_enabled'] ? 'checked' : ''; ?>>
                                    <label for="b2b_enabled">Enable B2B Enquiry</label>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="chat_enabled" name="chat_enabled" <?php echo $settings['chat_enabled'] ? 'checked' : ''; ?>>
                                    <label for="chat_enabled">Enable Chat Scheme</label>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="reviews_enabled" name="reviews_enabled" <?php echo $settings['reviews_enabled'] ?? 1 ? 'checked' : ''; ?>>
                                    <label for="reviews_enabled">Enable Product Reviews</label>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="wishlist_enabled" name="wishlist_enabled" <?php echo $settings['wishlist_enabled'] ?? 1 ? 'checked' : ''; ?>>
                                    <label for="wishlist_enabled">Enable Wishlist</label>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="compare_enabled" name="compare_enabled" <?php echo $settings['compare_enabled'] ?? 1 ? 'checked' : ''; ?>>
                                    <label for="compare_enabled">Enable Product Comparison</label>
                                </div>
                            </div>
                            
                            <!-- SEO & Analytics Settings -->
                            <div class="tab-content" id="seo">
                                <div class="form-group">
                                    <label for="meta_title">Meta Title</label>
                                    <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo $settings['meta_title'] ?? $settings['site_name']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="meta_description">Meta Description</label>
                                    <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo $settings['meta_description'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="meta_keywords">Meta Keywords</label>
                                    <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?php echo $settings['meta_keywords'] ?? ''; ?>">
                                    <small class="form-text">Separate keywords with commas</small>
                                </div>
                                
                                <h3 class="settings-subtitle">Analytics</h3>
                                
                                <div class="form-group">
                                    <label for="google_analytics_id">Google Analytics ID</label>
                                    <input type="text" id="google_analytics_id" name="google_analytics_id" class="form-control" value="<?php echo $settings['google_analytics_id'] ?? ''; ?>" placeholder="UA-XXXXXXXXX-X or G-XXXXXXXXXX">
                                </div>
                                
                                <div class="form-group">
                                    <label for="facebook_pixel_id">Facebook Pixel ID</label>
                                    <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" class="form-control" value="<?php echo $settings['facebook_pixel_id'] ?? ''; ?>" placeholder="XXXXXXXXXXXXXXXXXX">
                                </div>
                            </div>
                            
                            <!-- Payment Settings -->
                            <div class="tab-content" id="payment">
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="cod_enabled" name="cod_enabled" <?php echo $settings['cod_enabled'] ?? 1 ? 'checked' : ''; ?>>
                                    <label for="cod_enabled">Enable Cash on Delivery</label>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="online_payment_enabled" name="online_payment_enabled" <?php echo $settings['online_payment_enabled'] ?? 1 ? 'checked' : ''; ?>>
                                    <label for="online_payment_enabled">Enable Online Payment</label>
                                </div>
                                
                                <h3 class="settings-subtitle">Razorpay Settings</h3>
                                
                                <div class="form-group">
                                    <label for="razorpay_key_id">Razorpay Key ID</label>
                                    <input type="text" id="razorpay_key_id" name="razorpay_key_id" class="form-control" value="<?php echo $settings['razorpay_key_id'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="razorpay_key_secret">Razorpay Key Secret</label>
                                    <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" class="form-control" value="<?php echo $settings['razorpay_key_secret'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current button and content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // File upload
            const fileInputs = document.querySelectorAll('.file-input');
            
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
                    this.parentElement.querySelector('.file-name').textContent = fileName;
                });
            });
        });
    </script>
</body>
</html>
