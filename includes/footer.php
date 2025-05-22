<?php
// Get site options for customization if not already loaded
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Set default values if not set
$defaults = [
    'site_name' => 'Vamsi Crackers',
    'footer_columns' => 4,
    'footer_copyright' => '© ' . date('Y') . ' Vamsi Crackers. All Rights Reserved.'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Determine footer column class based on setting
$footerColumnClass = 'col-md-3'; // Default for 4 columns
if ($settings['footer_columns'] == 3) {
    $footerColumnClass = 'col-md-4';
} elseif ($settings['footer_columns'] == 2) {
    $footerColumnClass = 'col-md-6';
}
?>
<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="all-items.php">Products</a></li>
                <li><a href="about-us.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="terms.php">Terms & Conditions</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Customer Service</h3>
            <ul>
                <li><a href="payment.php">Payment Information</a></li>
                <li><a href="shipping.php">Shipping Information</a></li>
                <li><a href="returns.php">Returns Policy</a></li>
                <li><a href="faq.php">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Contact Us</h3>
            <p>Email: info@fireworksstore.com</p>
            <p>Phone: +91 1234567890</p>
            <p>Address: Your Store Address</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Fireworks Store. All rights reserved.</p>
    </div>
</footer>

<style>
    .site-footer {
        background-color: #1a1a1a;
        color: #ffffff;
        padding: 3rem 0 1rem;
        margin-top: 3rem;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        padding: 0 1rem;
    }

    .footer-section h3 {
        color: #ffd700;
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-section ul li {
        margin-bottom: 0.5rem;
    }

    .footer-section a {
        color: #ffffff;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-section a:hover {
        color: #ffd700;
    }

    .footer-bottom {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #333;
    }

    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            text-align: center;
        }
    }
</style>

<!-- JavaScript -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
