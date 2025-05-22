<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();

$page_title = "Terms and Conditions";

// Get terms content from database
try {
    $stmt = $pdo->prepare("SELECT content FROM pages WHERE slug = 'terms'");
    $stmt->execute();
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    $content = $page ? $page['content'] : '';
} catch (PDOException $e) {
    error_log("Terms page error: " . $e->getMessage());
    $content = '';
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Terms and Conditions</h1>
                </div>
                <div class="card-body">
                    <?php if (empty($content)): ?>
                        <div class="alert alert-info">
                            <p><strong>Last Updated:</strong> May 1, 2023</p>
                            
                            <h2>1. Introduction</h2>
                            <p>Welcome to Fireworks E-commerce ("we," "our," or "us"). These Terms and Conditions govern your use of our website and services. By accessing or using our website, you agree to be bound by these Terms.</p>
                            
                            <h2>2. Use of Our Services</h2>
                            <p>You must be at least 18 years old to purchase fireworks from our website. You agree to provide accurate and complete information when creating an account or making a purchase.</p>
                            
                            <h2>3. Products and Pricing</h2>
                            <p>All product descriptions and specifications are subject to change without notice. We reserve the right to modify prices at any time. All prices are displayed in USD unless otherwise specified.</p>
                            
                            <h2>4. Orders and Payment</h2>
                            <p>All orders are subject to acceptance and availability. We reserve the right to refuse any order. Payment must be made at the time of ordering. We accept major credit cards and other payment methods as indicated on our website.</p>
                            
                            <h2>5. Shipping and Delivery</h2>
                            <p>Shipping costs and delivery times vary depending on your location and the products ordered. We are not responsible for delays caused by customs or other factors outside our control.</p>
                            
                            <h2>6. Returns and Refunds</h2>
                            <p>Please refer to our Return Policy for information on returns, refunds, and exchanges.</p>
                            
                            <h2>7. Safety and Compliance</h2>
                            <p>You are responsible for using our products safely and in compliance with all applicable laws and regulations. Fireworks can be dangerous if misused. Always read and follow all safety instructions.</p>
                            
                            <h2>8. Intellectual Property</h2>
                            <p>All content on our website, including text, graphics, logos, and images, is our property and is protected by copyright and other intellectual property laws.</p>
                            
                            <h2>9. Privacy</h2>
                            <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect, use, and protect your personal information.</p>
                            
                            <h2>10. Limitation of Liability</h2>
                            <p>To the fullest extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or products.</p>
                            
                            <h2>11. Changes to Terms</h2>
                            <p>We may modify these Terms at any time. Your continued use of our website after any changes indicates your acceptance of the modified Terms.</p>
                            
                            <h2>12. Contact Us</h2>
                            <p>If you have any questions about these Terms, please contact us at support@fireworks-ecommerce.com.</p>
                        </div>
                    <?php else: ?>
                        <div class="terms-content">
                            <?php echo $content; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
