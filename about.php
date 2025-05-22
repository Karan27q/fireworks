<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();

$page_title = "About Us";

// Get about content from database
try {
    $stmt = $pdo->prepare("SELECT content FROM pages WHERE slug = 'about'");
    $stmt->execute();
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    $content = $page ? $page['content'] : '';
} catch (PDOException $e) {
    error_log("About page error: " . $e->getMessage());
    $content = '';
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">About Us</h1>
                </div>
                <div class="card-body">
                    <?php if (empty($content)): ?>
                        <div class="about-content">
                            <div class="row mb-5">
                                <div class="col-md-6">
                                    <img src="assets/images/about-us.jpg" alt="About Fireworks E-commerce" class="img-fluid rounded">
                                </div>
                                <div class="col-md-6">
                                    <h2>Our Story</h2>
                                    <p>Founded in 2010, Fireworks E-commerce has grown from a small local shop to one of the leading online retailers of fireworks in the country. Our journey began with a simple passion for bringing joy and excitement to celebrations.</p>
                                    <p>What started as a family business has now expanded into a comprehensive online platform offering a wide range of fireworks for all occasions, from small backyard gatherings to large-scale events.</p>
                                </div>
                            </div>
                            
                            <h2>Our Mission</h2>
                            <p>At Fireworks E-commerce, our mission is to provide high-quality fireworks that create memorable experiences for our customers. We believe that celebrations should be spectacular, and we're committed to helping you make every moment special.</p>
                            
                            <h2>Our Values</h2>
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
                                            <h3>Safety First</h3>
                                            <p>We prioritize safety in all our products and provide detailed guidelines for proper use.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-star fs-1 text-primary mb-3"></i>
                                            <h3>Quality</h3>
                                            <p>We source only the best fireworks from trusted manufacturers to ensure exceptional quality.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-people fs-1 text-primary mb-3"></i>
                                            <h3>Customer Service</h3>
                                            <p>We're dedicated to providing excellent customer service and support at every step.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h2 class="mt-5">Our Team</h2>
                            <p>Our team consists of fireworks enthusiasts and experts who are passionate about what they do. With years of experience in the industry, we have the knowledge and expertise to help you find the perfect fireworks for any occasion.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <img src="assets/images/team-1.jpg" class="card-img-top" alt="Team Member">
                                        <div class="card-body">
                                            <h5 class="card-title">John Smith</h5>
                                            <p class="card-text">Founder & CEO</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <img src="assets/images/team-2.jpg" class="card-img-top" alt="Team Member">
                                        <div class="card-body">
                                            <h5 class="card-title">Sarah Johnson</h5>
                                            <p class="card-text">Operations Manager</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <img src="assets/images/team-3.jpg" class="card-img-top" alt="Team Member">
                                        <div class="card-body">
                                            <h5 class="card-title">Michael Chen</h5>
                                            <p class="card-text">Product Specialist</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <img src="assets/images/team-4.jpg" class="card-img-top" alt="Team Member">
                                        <div class="card-body">
                                            <h5 class="card-title">Emily Davis</h5>
                                            <p class="card-text">Customer Support</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="about-content">
                            <?php echo $content; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
