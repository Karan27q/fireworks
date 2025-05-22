<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fireworks Store</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <style>
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
    <?php
    // Get site settings
    $settings_query = "SELECT * FROM site_settings WHERE id = 1";
    $settings_result = mysqli_query($conn, $settings_query);
    $settings = mysqli_fetch_assoc($settings_result);
    ?>
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
                <a href="#">Track Order</a>
                <a href="#">Combo Products</a>
                <?php if(isset($_SESSION['user_id'])): ?>
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
                <li><a href="all-items.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'all-items.php' ? 'active' : ''; ?>">ALL ITEM</a></li>
                <li><a href="#">NEW ARRIVALS</a></li>
                <li><a href="#">QUICK SHOPPING</a></li>
                <li><a href="about-us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about-us.php' ? 'active' : ''; ?>">ABOUT US</a></li>
                <li><a href="#">SHIPPING POLICY</a></li>
                <li><a href="payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">PAYMENT DETAILS</a></li>
                <li><a href="#">TESTIMONIAL</a></li>
            </ul>
        </nav>
    </header>
</body>
</html>
