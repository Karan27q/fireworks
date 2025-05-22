<?php
// Include database connection
include 'includes/db_connect.php';

// Set page title
$pageTitle = "About Us";

// Get about us content from database
$query = "SELECT * FROM pages WHERE slug = 'about-us' LIMIT 1";
$result = mysqli_query($conn, $query);
$aboutPage = mysqli_fetch_assoc($result);

// Get team members
$teamQuery = "SELECT * FROM team_members WHERE active = 1 ORDER BY display_order ASC";
$teamResult = mysqli_query($conn, $teamQuery);

if (!$teamResult) {
    die("Team members query failed: " . mysqli_error($conn));
}

$teamMembers = mysqli_fetch_all($teamResult, MYSQLI_ASSOC);

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="about-us-page">
    <div class="container">
        <div class="page-header">
            <h1>About Us</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; <span>About Us</span>
            </div>
        </div>
        
        <div class="about-content">
            <?php if($aboutPage && $aboutPage['content']): ?>
                <?php echo $aboutPage['content']; ?>
            <?php else: ?>
                <div class="about-intro">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="assets/images/about-us.jpg" alt="About Vamsi Crackers" class="img-fluid rounded">
                        </div>
                        <div class="col-md-6">
                            <h2>Our Story</h2>
                            <p>Founded in 2005, Vamsi Crackers has grown from a small local shop to one of the leading fireworks retailers in India. Our journey began with a simple passion for bringing joy and excitement to celebrations across the country.</p>
                            <p>What started as a family business has now expanded into a comprehensive online platform offering a wide range of fireworks for all occasions, from small family gatherings to large-scale events and festivals.</p>
                        </div>
                    </div>
                </div>
                
                <div class="about-mission">
                    <h2>Our Mission</h2>
                    <p>At Vamsi Crackers, our mission is to provide high-quality fireworks that create memorable experiences for our customers. We believe that celebrations should be spectacular, and we're committed to helping you make every moment special.</p>
                    <p>We strive to offer the best selection of fireworks at competitive prices, with exceptional customer service and reliable delivery across India.</p>
                </div>
                
                <div class="about-values">
                    <h2>Our Values</h2>
                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Safety First</h3>
                            <p>We prioritize safety in all our products and provide detailed guidelines for proper use.</p>
                        </div>
                        
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3>Quality</h3>
                            <p>We source only the best fireworks from trusted manufacturers to ensure exceptional quality.</p>
                        </div>
                        
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Customer Service</h3>
                            <p>We're dedicated to providing excellent customer service and support at every step.</p>
                        </div>
                        
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h3>Reliable Delivery</h3>
                            <p>We ensure timely and secure delivery of your orders across India.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($teamMembers)): ?>
            <div class="team-section">
                <h2>Our Team</h2>
                <div class="team-grid <?php echo $isMobile ? 'mobile-team-grid' : ''; ?>">
                    <?php foreach($teamMembers as $member): ?>
                    <div class="team-card <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                        <?php if($member['image']): ?>
                        <img src="uploads/team/<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/team/' . $member['image'] . '"' : ''; ?>>
                        <?php endif; ?>
                        <div class="team-info">
                            <h3><?php echo $member['name']; ?></h3>
                            <p class="position"><?php echo $member['position']; ?></p>
                            <?php if($member['bio']): ?>
                            <p class="bio"><?php echo $member['bio']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="testimonials-section">
                <h2>What Our Customers Say</h2>
                <div class="testimonials-slider <?php echo $isMobile ? 'mobile-testimonials' : ''; ?>">
                    <?php
                    $testimonialsQuery = "SELECT * FROM testimonials WHERE active = 1 ORDER BY id DESC LIMIT 5";
                    $testimonialsResult = mysqli_query($conn, $testimonialsQuery);
                    $testimonials = mysqli_fetch_all($testimonialsResult, MYSQLI_ASSOC);
                    
                    foreach($testimonials as $testimonial):
                    ?>
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"<?php echo $testimonial['content']; ?>"</p>
                        </div>
                        <div class="testimonial-author">
                            <?php if($testimonial['image']): ?>
                            <img src="uploads/testimonials/<?php echo $testimonial['image']; ?>" alt="<?php echo $testimonial['name']; ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/testimonials/' . $testimonial['image'] . '"' : ''; ?>>
                            <?php endif; ?>
                            <div class="author-info">
                                <h4><?php echo $testimonial['name']; ?></h4>
                                <?php if($testimonial['position']): ?>
                                <p><?php echo $testimonial['position']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Include mobile bottom navigation for mobile devices -->
<?php if($isMobile): ?>
<?php include 'includes/mobile_bottom_nav.php'; ?>
<?php endif; ?>

<!-- Include footer -->
<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Testimonials slider
        const testimonialCards = document.querySelectorAll('.testimonial-card');
        let currentTestimonial = 0;
        
        function showTestimonial(index) {
            testimonialCards.forEach((card, i) => {
                card.style.display = i === index ? 'block' : 'none';
            });
        }
        
        function nextTestimonial() {
            currentTestimonial = (currentTestimonial + 1) % testimonialCards.length;
            showTestimonial(currentTestimonial);
        }
        
        // Initialize testimonials
        if (testimonialCards.length > 0) {
            showTestimonial(0);
            setInterval(nextTestimonial, 5000);
        }
        
        // Lazy loading for mobile images
        if ('IntersectionObserver' in window) {
            const lazyImages = document.querySelectorAll('.lazy-image');
            
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-image');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    });
</script>
