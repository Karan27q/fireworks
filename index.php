<?php
// Include database connection
include 'includes/db_connect.php';
// Include header
include 'includes/header.php';

// Fetch featured categories
$query = "SELECT * FROM categories ORDER BY display_order ASC LIMIT 6";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch banner information
$bannerQuery = "SELECT * FROM banners WHERE active = 1 ORDER BY display_order ASC LIMIT 1";
$bannerResult = mysqli_query($conn, $bannerQuery);
$banner = mysqli_fetch_assoc($bannerResult);

// Get site options for customization
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
if (!$settings_result) {
    die("Query failed (site_settings): " . mysqli_error($conn));
}
$settings = mysqli_fetch_assoc($settings_result);

// Set default values if not set
$defaults = [
    'homepage_show_featured_categories' => 1,
    'homepage_featured_categories_title' => 'Featured Categories',
    'homepage_show_featured_products' => 1,
    'homepage_featured_products_title' => 'Featured Products',
    'homepage_show_new_arrivals' => 0,
    'homepage_new_arrivals_title' => 'New Arrivals',
    'homepage_show_testimonials' => 0,
    'homepage_testimonials_title' => 'Customer Testimonials'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!-- Main Content -->
<main class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Welcome to Our Store</h1>
                <p>Discover our wide range of quality products</p>
                <a href="products.php" class="btn btn-primary">Shop Now</a>
            </div>
            <?php if($banner): ?>
            <div class="hero-image">
                <img src="uploads/banners/<?php echo $banner['image_path']; ?>" alt="<?php echo $banner['alt_text']; ?>">
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Minimum Purchase Notice -->
    <div class="notice-banner">
        <div class="container">
            <div class="notice-content">
                <i class="fas fa-info-circle"></i>
                <p>Minimum Purchase Value INR 2500. Freight charges extra.</p>
                <p>Service possible entire INDIA including North Eastern States. <a href="shipping-policy.php" class="policy-link">View Shipping Policy</a></p>
            </div>
        </div>
    </div>

    <!-- Featured Categories -->
    <?php if (isset($settings['homepage_show_featured_categories']) && $settings['homepage_show_featured_categories']): ?>
    <section class="featured-categories">
        <div class="container">
            <h2 class="section-title"><?php echo $settings['homepage_featured_categories_title']; ?></h2>
            <div class="categories-grid">
                <?php foreach($categories as $category): ?>
                <div class="category-card">
                    <div class="category-content">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <?php if($category['multilingual_name']): ?>
                            <h4><?php echo htmlspecialchars($category['multilingual_name']); ?></h4>
                        <?php endif; ?>
                        
                        <?php
                        // Fetch subcategories
                        $subcatQuery = "SELECT * FROM subcategories WHERE category_id = {$category['id']} ORDER BY display_order ASC LIMIT 5";
                        $subcatResult = mysqli_query($conn, $subcatQuery);
                        $subcategories = mysqli_fetch_all($subcatResult, MYSQLI_ASSOC);
                        ?>
                        
                        <ul class="subcategory-list">
                            <?php foreach($subcategories as $subcat): ?>
                                <li>
                                    <a href="products.php?subcategory=<?php echo $subcat['id']; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                        <?php echo htmlspecialchars($subcat['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="category.php?id=<?php echo $category['id']; ?>" class="view-more">
                            View More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <?php if (isset($settings['homepage_show_featured_products']) && $settings['homepage_show_featured_products']): ?>
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title"><?php echo $settings['homepage_featured_products_title']; ?></h2>
            <div class="products-grid">
                <?php
                $featuredQuery = "SELECT p.*, c.name as category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                WHERE p.featured = 1 AND p.active = 1 
                                ORDER BY p.id DESC LIMIT 8";
                $featuredResult = mysqli_query($conn, $featuredQuery);
                $featuredProducts = mysqli_fetch_all($featuredResult, MYSQLI_ASSOC);
                
                foreach($featuredProducts as $product):
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if(!empty($product['image']) && file_exists("uploads/products/{$product['image']}")): ?>
                            <img src="uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="uploads/products/no-image.png" alt="No Image">
                        <?php endif; ?>
                        <div class="product-overlay">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-quick-view">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn-add-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-category">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </div>
                        <div class="product-price">
                            ₹<?php echo number_format($product['price'], 2); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-outline-primary">View All Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- New Arrivals -->
    <?php if (isset($settings['homepage_show_new_arrivals']) && $settings['homepage_show_new_arrivals']): ?>
    <section class="new-arrivals">
        <div class="container">
            <h2 class="section-title"><?php echo $settings['homepage_new_arrivals_title']; ?></h2>
            <div class="products-grid">
                <?php
                $newArrivalsQuery = "SELECT p.*, c.name as category_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   WHERE p.new_arrival = 1 AND p.active = 1 
                                   ORDER BY p.id DESC LIMIT 8";
                $newArrivalsResult = mysqli_query($conn, $newArrivalsQuery);
                $newArrivals = mysqli_fetch_all($newArrivalsResult, MYSQLI_ASSOC);
                
                foreach($newArrivals as $product):
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if(!empty($product['image']) && file_exists("uploads/products/{$product['image']}")): ?>
                            <img src="uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="uploads/products/no-image.png" alt="No Image">
                        <?php endif; ?>
                        <div class="product-overlay">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-quick-view">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn-add-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-category">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </div>
                        <div class="product-price">
                            ₹<?php echo number_format($product['price'], 2); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonials -->
    <?php if (isset($settings['homepage_show_testimonials']) && $settings['homepage_show_testimonials']): ?>
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title"><?php echo $settings['homepage_testimonials_title']; ?></h2>
            <div class="testimonials-slider">
                <?php
                $testimonialsQuery = "SELECT * FROM testimonials WHERE active = 1 ORDER BY id DESC LIMIT 3";
                $testimonialsResult = mysqli_query($conn, $testimonialsQuery);
                $testimonials = mysqli_fetch_all($testimonialsResult, MYSQLI_ASSOC);
                
                foreach($testimonials as $testimonial):
                ?>
                <div class="testimonial-card">
                    <?php if($testimonial['image']): ?>
                        <div class="testimonial-image">
                            <img src="uploads/testimonials/<?php echo $testimonial['image']; ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="testimonial-content">
                        <div class="testimonial-text">
                            <i class="fas fa-quote-left"></i>
                            <p><?php echo htmlspecialchars($testimonial['content']); ?></p>
                        </div>
                        <div class="testimonial-author">
                            <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                            <?php if($testimonial['position']): ?>
                                <p><?php echo htmlspecialchars($testimonial['position']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<style>
/* General Styles */
.home-page {
    overflow-x: hidden;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 2rem;
    color: #333;
    position: relative;
    padding-bottom: 1rem;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background-color: #007bff;
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 4rem 0;
    margin-bottom: 2rem;
}

.hero-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.hero-text {
    flex: 1;
    padding-right: 2rem;
}

.hero-text h1 {
    font-size: 3rem;
    color: #333;
    margin-bottom: 1rem;
}

.hero-text p {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 2rem;
}

.hero-image {
    flex: 1;
    max-width: 600px;
}

.hero-image img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Notice Banner */
.notice-banner {
    background-color: #fff3cd;
    padding: 1rem 0;
    margin-bottom: 3rem;
}

.notice-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    color: #856404;
}

.notice-content i {
    font-size: 1.5rem;
}

.notice-banner .policy-link {
    color: #0056b3;
    text-decoration: underline;
    margin-left: 5px;
    font-weight: 500;
}

.notice-banner .policy-link:hover {
    color: #003d82;
}

/* Featured Categories */
.featured-categories {
    padding: 4rem 0;
    background-color: #f8f9fa;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.category-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
}

.category-content {
    padding: 1.5rem;
}

.category-content h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 0.5rem;
}

.category-content h4 {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 1rem;
}

.subcategory-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem 0;
}

.subcategory-list li {
    margin-bottom: 0.5rem;
}

.subcategory-list a {
    color: #666;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.3s ease;
}

.subcategory-list a:hover {
    color: #007bff;
}

.view-more {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: gap 0.3s ease;
}

.view-more:hover {
    gap: 0.8rem;
}

/* Featured Products */
.featured-products {
    padding: 4rem 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-image {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
}

.product-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.btn-quick-view,
.btn-add-cart {
    background: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-quick-view:hover,
.btn-add-cart:hover {
    background: #007bff;
    color: white;
}

.product-info {
    padding: 1.5rem;
}

.product-title {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.product-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-title a:hover {
    color: #007bff;
}

.product-category {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 600;
    color: #28a745;
}

/* Testimonials */
.testimonials {
    padding: 4rem 0;
    background-color: #f8f9fa;
}

.testimonials-slider {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.testimonial-card {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.testimonial-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 1.5rem;
}

.testimonial-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.testimonial-text {
    text-align: center;
    margin-bottom: 1.5rem;
}

.testimonial-text i {
    color: #007bff;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.testimonial-text p {
    color: #666;
    font-style: italic;
    line-height: 1.6;
}

.testimonial-author {
    text-align: center;
}

.testimonial-author h4 {
    color: #333;
    margin-bottom: 0.25rem;
}

.testimonial-author p {
    color: #666;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }

    .hero-text {
        padding-right: 0;
    }

    .hero-image {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .section-title {
        font-size: 1.75rem;
    }

    .hero-text h1 {
        font-size: 2.5rem;
    }

    .notice-content {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .hero-text h1 {
        font-size: 2rem;
    }

    .products-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}
</style>

<?php include 'includes/footer.php'; ?>
