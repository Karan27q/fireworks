<?php
session_start();

// Define admin path constant
define('ADMIN_PATH', true);

// Include database connection
include '../includes/db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Site Branding
    if (isset($_POST['update_branding'])) {
        $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
        $site_tagline = mysqli_real_escape_string($conn, $_POST['site_tagline']);
        $primary_color = mysqli_real_escape_string($conn, $_POST['primary_color']);
        $secondary_color = mysqli_real_escape_string($conn, $_POST['secondary_color']);
        $footer_text = mysqli_real_escape_string($conn, $_POST['footer_text']);
        
        // Update settings in database
        $update_query = "UPDATE site_settings SET 
            site_name = '$site_name',
            license_text = '$site_tagline',
            primary_color = '$primary_color',
            secondary_color = '$secondary_color',
            footer_text = '$footer_text'
            WHERE id = 1";
        
        if(mysqli_query($conn, $update_query)) {
            // Generate custom CSS file
            $css = ":root {\n";
            $css .= "  --primary-color: {$primary_color};\n";
            $css .= "  --secondary-color: {$secondary_color};\n";
            $css .= "}\n\n";
            $css .= ".main-nav { background-color: var(--primary-color); }\n";
            $css .= ".btn-primary, .search-btn { background-color: var(--primary-color); }\n";
            $css .= ".btn-primary:hover, .search-btn:hover { background-color: var(--secondary-color); }\n";
            $css .= ".top-bar { background-color: var(--secondary_color); }\n";
            
            $css_file = '../assets/css/custom.css';
            if (file_put_contents($css_file, $css) === false) {
                $error = "Error: Could not write to CSS file. Please check file permissions.";
            } else {
                $message = "Site branding updated successfully.";
            }
        } else {
            $error = "Error updating site branding: " . mysqli_error($conn);
        }
    }
    
    // Update Homepage Layout
    if (isset($_POST['update_homepage'])) {
        $show_featured_categories = isset($_POST['show_featured_categories']) ? 1 : 0;
        $featured_categories_title = mysqli_real_escape_string($conn, $_POST['featured_categories_title']);
        $show_featured_products = isset($_POST['show_featured_products']) ? 1 : 0;
        $featured_products_title = mysqli_real_escape_string($conn, $_POST['featured_products_title']);
        $show_new_arrivals = isset($_POST['show_new_arrivals']) ? 1 : 0;
        $new_arrivals_title = mysqli_real_escape_string($conn, $_POST['new_arrivals_title']);
        $show_testimonials = isset($_POST['show_testimonials']) ? 1 : 0;
        $testimonials_title = mysqli_real_escape_string($conn, $_POST['testimonials_title']);
        
        // Update settings in database
        $update_query = "UPDATE site_settings SET 
            homepage_show_featured_categories = $show_featured_categories,
            homepage_featured_categories_title = '$featured_categories_title',
            homepage_show_featured_products = $show_featured_products,
            homepage_featured_products_title = '$featured_products_title',
            homepage_show_new_arrivals = $show_new_arrivals,
            homepage_new_arrivals_title = '$new_arrivals_title',
            homepage_show_testimonials = $show_testimonials,
            homepage_testimonials_title = '$testimonials_title'
            WHERE id = 1";
        
        if(mysqli_query($conn, $update_query)) {
            $message = "Homepage layout updated successfully.";
        } else {
            $error = "Error updating homepage layout: " . mysqli_error($conn);
        }
    }
    
    // Update Header & Footer
    if (isset($_POST['update_header_footer'])) {
        $show_top_bar = isset($_POST['show_top_bar']) ? 1 : 0;
        $top_bar_text = mysqli_real_escape_string($conn, $_POST['top_bar_text']);
        $show_secondary_nav = isset($_POST['show_secondary_nav']) ? 1 : 0;
        $show_social_icons = isset($_POST['show_social_icons']) ? 1 : 0;
        $footer_columns = intval($_POST['footer_columns']);
        $footer_copyright = mysqli_real_escape_string($conn, $_POST['footer_copyright']);
        
        // Update settings in database
        $update_query = "UPDATE site_settings SET 
            header_show_top_bar = $show_top_bar,
            header_top_bar_text = '$top_bar_text',
            header_show_secondary_nav = $show_secondary_nav,
            header_show_social_icons = $show_social_icons,
            footer_columns = $footer_columns,
            footer_copyright = '$footer_copyright'
            WHERE id = 1";
        
        if(mysqli_query($conn, $update_query)) {
            $message = "Header and footer updated successfully.";
        } else {
            $error = "Error updating header and footer: " . mysqli_error($conn);
        }
    }
    
    // Update Product Display
    if (isset($_POST['update_product_display'])) {
        $products_per_page = intval($_POST['products_per_page']);
        $product_layout = mysqli_real_escape_string($conn, $_POST['product_layout']);
        $show_product_rating = isset($_POST['show_product_rating']) ? 1 : 0;
        $show_product_stock = isset($_POST['show_product_stock']) ? 1 : 0;
        $show_related_products = isset($_POST['show_related_products']) ? 1 : 0;
        $related_products_title = mysqli_real_escape_string($conn, $_POST['related_products_title']);
        
        // Update settings in database
        $update_query = "UPDATE site_settings SET 
            product_products_per_page = $products_per_page,
            product_layout = '$product_layout',
            product_show_rating = $show_product_rating,
            product_show_stock = $show_product_stock,
            product_show_related = $show_related_products,
            product_related_title = '$related_products_title'
            WHERE id = 1";
        
        if(mysqli_query($conn, $update_query)) {
            $message = "Product display settings updated successfully.";
        } else {
            $error = "Error updating product display settings: " . mysqli_error($conn);
        }
    }
    
    // Test WhatsApp Notification
    if (isset($_POST['test_whatsapp'])) {
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
        
        // Include WhatsApp notification functions
        include_once '../includes/whatsapp_notification.php';
        
        // Send test message
        $result = test_whatsapp_notification($phone_number);
        
        if ($result) {
            $message = "Test WhatsApp message sent successfully to $phone_number.";
        } else {
            $error = "Failed to send test WhatsApp message. Please check your settings.";
        }
    }
}

// Get site settings
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Set default values if not set
$defaults = [
    'site_name' => 'Vamsi Crackers',
    'license_text' => 'Quality Fireworks for All Occasions',
    'primary_color' => '#4caf50',
    'secondary_color' => '#ff6b00',
    'footer_text' => '© ' . date('Y') . ' Vamsi Crackers. All Rights Reserved.',
    'homepage_featured_categories_title' => 'Featured Categories',
    'homepage_featured_products_title' => 'Featured Products',
    'homepage_new_arrivals_title' => 'New Arrivals',
    'homepage_testimonials_title' => 'Customer Testimonials',
    'header_top_bar_text' => 'Central Government Approved License Seller',
    'footer_columns' => 4,
    'footer_copyright' => '© ' . date('Y') . ' Vamsi Crackers. All Rights Reserved.',
    'product_products_per_page' => 12,
    'product_layout' => 'grid',
    'product_related_title' => 'Related Products'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Customization - Vamsi Crackers</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.css">
    <style>
        .color-picker {
            width: 100%;
        }
        
        .preview-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .preview-header {
            background-color: v-bind(primaryColor);
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .preview-button {
            background-color: v-bind(primaryColor);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .preview-button:hover {
            background-color: v-bind(secondaryColor);
        }
        
        .preview-accent {
            color: v-bind(secondaryColor);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Page Content -->
            <div class="content">
                <div class="content-header">
                    <h1>Site Customization</h1>
                    <div class="actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="content-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Site Branding -->
                            <div class="card">
                                <div class="card-header">
                                    <h2>Site Branding</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="site_name">Site Name</label>
                                                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo $settings['site_name']; ?>" required>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                <label for="site_tagline">Tagline</label>
                                                <input type="text" id="site_tagline" name="site_tagline" class="form-control" value="<?php echo $settings['license_text']; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="primary_color">Primary Color</label>
                                                <input type="text" id="primary_color" name="primary_color" class="form-control color-picker" value="<?php echo $settings['primary_color']; ?>">
                                                <small class="form-text text-muted">Used for main navigation, buttons, etc.</small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                <label for="secondary_color">Secondary Color</label>
                                                <input type="text" id="secondary_color" name="secondary_color" class="form-control color-picker" value="<?php echo $settings['secondary_color']; ?>">
                                                <small class="form-text text-muted">Used for accents, hover states, etc.</small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="footer_text">Footer Text</label>
                                            <textarea id="footer_text" name="footer_text" class="form-control" rows="2"><?php echo $settings['footer_text']; ?></textarea>
                                            <small class="form-text text-muted">Copyright text or additional information for the footer.</small>
                                        </div>
                                        
                                        <div class="preview-box">
                                            <h3>Preview</h3>
                                            <div class="preview-header" id="preview-header">
                                                Vamsi Crackers
                                            </div>
                                            <p>This is how your site colors will look. The <span class="preview-accent" id="preview-accent">accent color</span> is used for highlights.</p>
                                            <button class="preview-button" id="preview-button">Sample Button</button>
                                        </div>
                                        
                                        <div class="form-group mt-3">
                                            <button type="submit" name="update_branding" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Branding
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Homepage Layout -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Homepage Layout</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_featured_categories" name="show_featured_categories" value="1" <?php echo isset($settings['homepage_show_featured_categories']) && $settings['homepage_show_featured_categories'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_featured_categories">Show Featured Categories</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="featured_categories_title">Featured Categories Title</label>
                                            <input type="text" id="featured_categories_title" name="featured_categories_title" class="form-control" value="<?php echo $settings['homepage_featured_categories_title']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_featured_products" name="show_featured_products" value="1" <?php echo isset($settings['homepage_show_featured_products']) && $settings['homepage_show_featured_products'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_featured_products">Show Featured Products</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="featured_products_title">Featured Products Title</label>
                                            <input type="text" id="featured_products_title" name="featured_products_title" class="form-control" value="<?php echo $settings['homepage_featured_products_title']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_new_arrivals" name="show_new_arrivals" value="1" <?php echo isset($settings['homepage_show_new_arrivals']) && $settings['homepage_show_new_arrivals'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_new_arrivals">Show New Arrivals</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_arrivals_title">New Arrivals Title</label>
                                            <input type="text" id="new_arrivals_title" name="new_arrivals_title" class="form-control" value="<?php echo $settings['homepage_new_arrivals_title']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_testimonials" name="show_testimonials" value="1" <?php echo isset($settings['homepage_show_testimonials']) && $settings['homepage_show_testimonials'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_testimonials">Show Testimonials</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="testimonials_title">Testimonials Title</label>
                                            <input type="text" id="testimonials_title" name="testimonials_title" class="form-control" value="<?php echo $settings['homepage_testimonials_title']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="update_homepage" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Homepage Layout
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Header & Footer -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Header & Footer</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <h3>Header Options</h3>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_top_bar" name="show_top_bar" value="1" <?php echo isset($settings['header_show_top_bar']) && $settings['header_show_top_bar'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_top_bar">Show Top Bar</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="top_bar_text">Top Bar Text</label>
                                            <input type="text" id="top_bar_text" name="top_bar_text" class="form-control" value="<?php echo $settings['header_top_bar_text']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_secondary_nav" name="show_secondary_nav" value="1" <?php echo isset($settings['header_show_secondary_nav']) && $settings['header_show_secondary_nav'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_secondary_nav">Show Secondary Navigation</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_social_icons" name="show_social_icons" value="1" <?php echo isset($settings['header_show_social_icons']) && $settings['header_show_social_icons'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_social_icons">Show Social Icons</label>
                                            </div>
                                        </div>
                                        
                                        <h3 class="mt-4">Footer Options</h3>
                                        
                                        <div class="form-group">
                                            <label for="footer_columns">Footer Columns</label>
                                            <select id="footer_columns" name="footer_columns" class="form-control">
                                                <option value="2" <?php echo $settings['footer_columns'] == 2 ? 'selected' : ''; ?>>2 Columns</option>
                                                <option value="3" <?php echo $settings['footer_columns'] == 3 ? 'selected' : ''; ?>>3 Columns</option>
                                                <option value="4" <?php echo $settings['footer_columns'] == 4 ? 'selected' : ''; ?>>4 Columns</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="footer_copyright">Copyright Text</label>
                                            <input type="text" id="footer_copyright" name="footer_copyright" class="form-control" value="<?php echo $settings['footer_copyright']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="update_header_footer" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Header & Footer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Product Display -->
                            <div class="card">
                                <div class="card-header">
                                    <h2>Product Display</h2>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="products_per_page">Products Per Page</label>
                                            <input type="number" id="products_per_page" name="products_per_page" class="form-control" value="<?php echo $settings['product_products_per_page']; ?>" min="4" max="48" step="4">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="product_layout">Product Layout</label>
                                            <select id="product_layout" name="product_layout" class="form-control">
                                                <option value="grid" <?php echo $settings['product_layout'] == 'grid' ? 'selected' : ''; ?>>Grid</option>
                                                <option value="list" <?php echo $settings['product_layout'] == 'list' ? 'selected' : ''; ?>>List</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_product_rating" name="show_product_rating" value="1" <?php echo isset($settings['product_show_rating']) && $settings['product_show_rating'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_product_rating">Show Product Rating</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_product_stock" name="show_product_stock" value="1" <?php echo isset($settings['product_show_stock']) && $settings['product_show_stock'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_product_stock">Show Stock Status</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_related_products" name="show_related_products" value="1" <?php echo isset($settings['product_show_related']) && $settings['product_show_related'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_related_products">Show Related Products</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="related_products_title">Related Products Title</label>
                                            <input type="text" id="related_products_title" name="related_products_title" class="form-control" value="<?php echo $settings['product_related_title']; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="update_product_display" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Product Display
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Test WhatsApp Notification -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Test WhatsApp Notification</h2>
                                </div>
                                <div class="card-body">
                                    <p>Send a test WhatsApp message to verify your notification system is working correctly.</p>
                                    
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g. 919876543210" required>
                                            <small class="form-text text-muted">Include country code without + (e.g. 91 for India)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="test_whatsapp" class="btn btn-primary">
                                                <i class="fab fa-whatsapp"></i> Send Test Message
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Customization Help -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h2>Customization Help</h2>
                                </div>
                                <div class="card-body">
                                    <h4>Tips for Customization</h4>
                                    <ul>
                                        <li>Choose colors that reflect your brand identity</li>
                                        <li>Keep your site name consistent across all pages</li>
                                        <li>Use clear and descriptive section titles</li>
                                        <li>Test your site on different devices after customization</li>
                                        <li>Regularly update your homepage layout to showcase new products</li>
                                    </ul>
                                    
                                    <h4>Color Recommendations</h4>
                                    <p>For fireworks stores, consider these color combinations:</p>
                                    <ul>
                                        <li>Red (#e53935) and Gold (#ffd700)</li>
                                        <li>Blue (#1565c0) and Orange (#ff9800)</li>
                                        <li>Green (#4caf50) and Purple (#9c27b0)</li>
                                        <li>Black (#212121) and Red (#f44336)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.js"></script>
    <script>
        // Initialize color pickers
        $(document).ready(function() {
            $(".color-picker").spectrum({
                preferredFormat: "hex",
                showInput: true,
                showPalette: true,
                palette: [
                    ["#f44336", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#2196f3", "#03a9f4", "#00bcd4"],
                    ["#009688", "#4caf50", "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800", "#ff5722"]
                ]
            });
            
            // Update preview on color change
            $("#primary_color, #secondary_color").on("change", updatePreview);
            
            // Initial preview update
            updatePreview();
            
            function updatePreview() {
                const primaryColor = $("#primary_color").val();
                const secondaryColor = $("#secondary_color").val();
                
                $("#preview-header").css("background-color", primaryColor);
                $("#preview-button").css("background-color", primaryColor);
                $("#preview-accent").css("color", secondaryColor);
                
                // Add hover effect
                $("#preview-button").hover(
                    function() {
                        $(this).css("background-color", secondaryColor);
                    },
                    function() {
                        $(this).css("background-color", primaryColor);
                    }
                );
            }
        });
    </script>
</body>
</html>
