<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();

$page_title = "Contact Us";

// Get company information
try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key IN ('company_name', 'company_address', 'company_phone', 'company_email')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Settings error: " . $e->getMessage());
    $settings = [];
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Contact Us</h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Get in Touch</h2>
                            <p>We'd love to hear from you! Please fill out the form below and we'll get back to you as soon as possible.</p>
                            
                            <div id="contact-alert" class="alert d-none"></div>
                            
                            <form id="contact-form" method="post">
                                <div class="form-group mb-3">
                                    <label for="name">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="subject">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="message">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h2>Contact Information</h2>
                            <p>You can also reach us using the information below:</p>
                            
                            <div class="contact-info mt-4">
                                <div class="d-flex mb-3">
                                    <div class="icon-box me-3">
                                        <i class="bi bi-geo-alt fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5>Address</h5>
                                        <p><?php echo htmlspecialchars($settings['company_address'] ?? '123 Fireworks Ave, Sparkle City, SC 12345'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="icon-box me-3">
                                        <i class="bi bi-telephone fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5>Phone</h5>
                                        <p><?php echo htmlspecialchars($settings['company_phone'] ?? '(555) 123-4567'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="icon-box me-3">
                                        <i class="bi bi-envelope fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5>Email</h5>
                                        <p><?php echo htmlspecialchars($settings['company_email'] ?? 'info@fireworks-ecommerce.com'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="icon-box me-3">
                                        <i class="bi bi-clock fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5>Business Hours</h5>
                                        <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                                        Saturday: 10:00 AM - 4:00 PM<br>
                                        Sunday: Closed</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="social-media mt-4">
                                <h5>Follow Us</h5>
                                <div class="d-flex">
                                    <a href="#" class="me-3 text-primary fs-4"><i class="bi bi-facebook"></i></a>
                                    <a href="#" class="me-3 text-primary fs-4"><i class="bi bi-twitter"></i></a>
                                    <a href="#" class="me-3 text-primary fs-4"><i class="bi bi-instagram"></i></a>
                                    <a href="#" class="me-3 text-primary fs-4"><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-5">
                        <div class="col-12">
                            <h2>Frequently Asked Questions</h2>
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            What are your shipping options?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            We offer standard shipping (3-5 business days), express shipping (1-2 business days), and next-day delivery options. Shipping costs vary based on your location and the weight of your order.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            What is your return policy?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Due to the nature of fireworks products, we have a limited return policy. Unopened and unused products can be returned within 14 days of purchase. Please contact our customer service team for more information.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Are there any age restrictions for purchasing fireworks?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Yes, you must be at least 18 years old to purchase fireworks from our website. We may require age verification upon delivery.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            Do you ship internationally?
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Due to regulations regarding the transportation of fireworks, we currently only ship within the United States. International shipping is not available at this time.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#contact-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: 'ajax/contact_form.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#contact-alert').removeClass('d-none alert-danger alert-success');
                if (response.success) {
                    $('#contact-alert').addClass('alert-success').text(response.message);
                    $('#contact-form')[0].reset();
                } else {
                    $('#contact-alert').addClass('alert-danger').text(response.message);
                }
            },
            error: function() {
                $('#contact-alert').removeClass('d-none').addClass('alert-danger').text('An error occurred. Please try again later.');
            }
        });
    });
});
</script>

<!-- Google Map -->
<div class="container mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Our Location</h2>
                </div>
                <div class="card-body p-0">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3456.789012345678!2d-122.12345678901234!3d37.12345678901234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzfCsDA3JzI0LjQiTiAxMjLCsDA3JzI0LjQiVw!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
