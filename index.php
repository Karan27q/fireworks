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
<main>
  <!-- Hero Banner -->
  <div class="hero-banner">
    <img src="uploads/banners/<?php echo $banner['image_path']; ?>" alt="<?php echo $banner['alt_text']; ?>">
  </div>

  <!-- Minimum Purchase Notice -->
  <div class="min-purchase-notice">
    <p>Minimum Purchase Value INR 2500. Freight charges extra.</p>
    <p>Service possible entire INDIA including North Eastern States.</p>
  </div>

  <!-- Featured Categories -->
  <?php if (isset($settings['homepage_show_featured_categories']) && $settings['homepage_show_featured_categories']): ?>
<div class="categories-container">
  <h2 class="section-title"><?php echo $settings['homepage_featured_categories_title']; ?></h2>
  <?php foreach($categories as $category): ?>
    <div class="category-card">
      <h3><?php echo $category['name']; ?></h3>
      <?php if($category['multilingual_name']): ?>
        <h4><?php echo $category['multilingual_name']; ?></h4>
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
            <span class="bullet">▶</span>
            <a href="products.php?subcategory=<?php echo $subcat['id']; ?>"><?php echo $subcat['name']; ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      
      <a href="category.php?id=<?php echo $category['id']; ?>" class="view-more-btn">View More</a>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Featured Products -->
<?php if (isset($settings['homepage_show_featured_products']) && $settings['homepage_show_featured_products']): ?>
<div class="featured-products">
  <h2><?php echo $settings['homepage_featured_products_title']; ?></h2>
  <div class="products-grid">
    <?php
    $featuredQuery = "SELECT * FROM products WHERE featured = 1 AND active = 1 ORDER BY id DESC LIMIT 8";
    $featuredResult = mysqli_query($conn, $featuredQuery);
    $featuredProducts = mysqli_fetch_all($featuredResult, MYSQLI_ASSOC);
    
    foreach($featuredProducts as $product):
    ?>
      <div class="product-card">
        <img src="uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
        <h3><?php echo $product['name']; ?></h3>
        <p class="price">₹<?php echo $product['price']; ?></p>
        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
        <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- New Arrivals -->
<?php if (isset($settings['homepage_show_new_arrivals']) && $settings['homepage_show_new_arrivals']): ?>
<div class="featured-products new-arrivals">
  <h2><?php echo $settings['homepage_new_arrivals_title']; ?></h2>
  <div class="products-grid">
    <?php
    $newArrivalsQuery = "SELECT * FROM products WHERE new_arrival = 1 AND active = 1 ORDER BY id DESC LIMIT 8";
    $newArrivalsResult = mysqli_query($conn, $newArrivalsQuery);
    $newArrivals = mysqli_fetch_all($newArrivalsResult, MYSQLI_ASSOC);
    
    foreach($newArrivals as $product):
    ?>
      <div class="product-card">
        <img src="uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
        <h3><?php echo $product['name']; ?></h3>
        <p class="price">₹<?php echo $product['price']; ?></p>
        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
        <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Testimonials -->
<?php if (isset($settings['homepage_show_testimonials']) && $settings['homepage_show_testimonials']): ?>
<div class="testimonials-section">
  <h2><?php echo $settings['homepage_testimonials_title']; ?></h2>
  <div class="testimonials-container">
    <?php
    $testimonialsQuery = "SELECT * FROM testimonials WHERE active = 1 ORDER BY id DESC LIMIT 3";
    $testimonialsResult = mysqli_query($conn, $testimonialsQuery);
    $testimonials = mysqli_fetch_all($testimonialsResult, MYSQLI_ASSOC);
    
    foreach($testimonials as $testimonial):
    ?>
      <div class="testimonial-card">
        <?php if($testimonial['image']): ?>
          <img src="uploads/testimonials/<?php echo $testimonial['image']; ?>" alt="<?php echo $testimonial['name']; ?>" class="testimonial-image">
        <?php endif; ?>
        <div class="testimonial-content">
          <p>"<?php echo $testimonial['content']; ?>"</p>
          <div class="testimonial-author">
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
<?php endif; ?>
</main>

<!-- Include footer -->
<?php include 'includes/footer.php'; ?>
