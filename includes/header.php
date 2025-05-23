<?php
require_once 'db_connect.php';
require_once 'session_handler.php';

// Get site settings
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Get site options
$options_query = "SELECT * FROM site_options";
$options_result = mysqli_query($conn, $options_query);
$site_options = [];
while ($option = mysqli_fetch_assoc($options_result)) {
    $site_options[$option['option_name']] = $option['option_value'];
}

// Set default values if options don't exist
$site_options['site_name'] = $site_options['site_name'] ?? 'Your Store Name';
$site_options['site_description'] = $site_options['site_description'] ?? 'Your Store Description';
$site_options['currency'] = $site_options['currency'] ?? '₹';
$site_options['min_purchase'] = $site_options['min_purchase'] ?? '500';
$site_options['service_area'] = $site_options['service_area'] ?? 'Entire INDIA including North Eastern States';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $site_options['site_name'] : $site_options['site_name']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #0056b3;
        }

        body {
            margin: 0;
            font-family: sans-serif;
        }

        /* Top Bar Styles */
        .top-bar {
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        /* Header Section Styles */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-section .logo img {
            height: 50px;
        }

        .header-section .search-bar {
            display: flex;
            flex-grow: 1;
            margin: 0 2rem;
            max-width: 500px;
        }

        .header-section .search-bar input[type="text"] {
            flex-grow: 1;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            outline: none;
        }

        .header-section .search-bar button {
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .header-section .search-bar button:hover {
            background-color: var(--secondary-color);
        }

        .header-section .secondary-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .header-section .secondary-nav a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .header-section .secondary-nav a:hover {
            color: var(--secondary-color);
        }

        .header-section .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.2rem;
            color: var(--secondary-color);
            font-weight: bold;
            text-decoration: none;
        }

        .header-section .cart-icon img {
            height: 20px;
        }

        .header-section .cart-count {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            padding: 1px 5px;
            font-size: 0.7rem;
        }

        /* Main Navigation Styles */
        .main-nav {
            background-color: var(--primary-color);
            padding: 0.8rem 0;
        }

        .main-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }

        .main-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: background-color 0.3s ease;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .main-nav a:hover,
        .main-nav a.active {
            background-color: var(--secondary-color);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 1rem;
            }

            .header-section .search-bar {
                margin: 0;
                width: 100%;
                max-width: none;
            }

            .header-section .secondary-nav {
                justify-content: center;
                width: 100%;
                margin-top: 0.5rem;
            }

            .main-nav ul {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .main-nav a {
                width: 100%;
                text-align: center;
                padding: 0.8rem 1rem;
            }
        }

        /* Custom Styles for Spacing and Boxes */
        body {
            padding-top: 0;
        }

        .container {
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .card-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .alert {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 4px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <?php echo $settings['header_top_bar_text'] ?? 'Central Government Approved License Seller'; ?> &nbsp; | &nbsp; GST included in product price &nbsp; <img src="assets/images/google-pay-logo.png" alt="Google Pay" style="height: 15px; vertical-align: middle;">
    </div>

    <header>
        <div class="header-section">
            <div class="logo">
                <a href="index.php"><img src="assets/images/vamsi-crackers-logo.png" alt="<?php echo $settings['site_name'] ?? 'Vamsi Crackers'; ?>"></a>
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <button>Search</button>
            </div>
            <div class="secondary-nav">
                <a href="track-order.php">Track Order</a>
                <a href="combo-products.php">Combo Products</a>
                <a href="shipping-policy.php">Shipping Policy</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <?php if($_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin/">Admin Panel</a>
                    <?php endif; ?>
                    <a href="profile.php">My Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
                <a href="cart.php" class="cart-icon">
                    <img src="assets/images/cart-icon.png" alt="Cart">
                    My cart <span class="cart-count"><?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : '0'; ?></span>
                </a>
            </div>
        </div>

        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">HOME</a></li>
                <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">ALL ITEMS</a></li>
                <li><a href="#">NEW ARRIVALS</a></li>
                <li><a href="#">QUICK SHOPPING</a></li>
                <li><a href="about-us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about-us.php' ? 'active' : ''; ?>">ABOUT US</a></li>
                <li><a href="payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">PAYMENT DETAILS</a></li>
                <li><a href="#">TESTIMONIAL</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content py-4">
        <div class="container">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['flash_message'];
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
