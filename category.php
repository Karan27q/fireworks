<?php
// Include database connection
include 'includes/db_connect.php';

// Check if category ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$categoryId = (int)$_GET['id'];

// Get category details
$query = "SELECT * FROM categories WHERE id = $categoryId AND active = 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Check if category exists
if (mysqli_num_rows($result) === 0) {
    header('Location: index.php');
    exit;
}

$category = mysqli_fetch_assoc($result);

// Set page title
$pageTitle = $category['name'];

// Get subcategories
$subcategoriesQuery = "SELECT * FROM subcategories WHERE category_id = $categoryId AND active = 1 ORDER BY display_order ASC, name ASC";
$subcategoriesResult = mysqli_query($conn, $subcategoriesQuery);

if (!$subcategoriesResult) {
    die("Subcategories query failed: " . mysqli_error($conn));
}

$subcategories = mysqli_fetch_all($subcategoriesResult, MYSQLI_ASSOC);

// Get featured products from this category
$featuredProductsQuery = "SELECT * FROM products WHERE category_id = $categoryId AND featured = 1 AND active = 1 ORDER BY display_order ASC LIMIT 8";
$featuredProductsResult = mysqli_query($conn, $featuredProductsQuery);

if (!$featuredProductsResult) {
    die("Featured products query failed: " . mysqli_error($conn));
}

$featuredProducts = mysqli_fetch_all($featuredProductsResult, MYSQLI_ASSOC);

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="category-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; <span><?php echo htmlspecialchars($category['name']); ?></span>
            </div>
        </div>
        
        <?php if($category['description']): ?>
        <div class="category-description">
            <?php echo nl2br(htmlspecialchars($category['description'])); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($subcategories)): ?>
        <div class="subcategories-section">
            <h2>Subcategories</h2>
            <div class="subcategories-grid <?php echo $isMobile ? 'mobile-subcategories-grid' : ''; ?>">
                <?php foreach($subcategories as $subcategory): ?>
                <div class="subcategory-card <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                    <a href="products.php?subcategory=<?php echo $subcategory['id']; ?>">
                        <?php if($subcategory['image'] && file_exists("uploads/subcategories/{$subcategory['image']}")): ?>
                        <img src="uploads/subcategories/<?php echo htmlspecialchars($subcategory['image']); ?>" alt="<?php echo htmlspecialchars($subcategory['name']); ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/subcategories/' . htmlspecialchars($subcategory['image']) . '"' : ''; ?>>
                        <?php else: ?>
                        <img src="uploads/subcategories/no-image.png" alt="No Image" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($subcategory['name']); ?></h3>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($featuredProducts)): ?>
        <div class="featured-products-section">
            <h2>Featured Products</h2>
            <div class="products-grid <?php echo $isMobile ? 'mobile-products-grid' : ''; ?>">
                <?php foreach($featuredProducts as $product): ?>
                <div class="product-card <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                        <?php if($product['image'] && file_exists("uploads/products/{$product['image']}")): ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/products/' . htmlspecialchars($product['image']) . '"' : ''; ?>>
                        <?php else: ?>
                        <img src="uploads/products/no-image.png" alt="No Image" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                    </a>
                    <div class="product-card-actions">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                        <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="view-all-link">
                <a href="products.php?category=<?php echo $categoryId; ?>" class="btn btn-primary">View All Products in <?php echo htmlspecialchars($category['name']); ?></a>
            </div>
        </div>
        <?php else: ?>
        <div class="view-all-link">
            <a href="products.php?category=<?php echo $categoryId; ?>" class="btn btn-primary">View All Products in <?php echo htmlspecialchars($category['name']); ?></a>
        </div>
        <?php endif; ?>
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
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        
        addToCartButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Send AJAX request
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        this.innerHTML = '<i class="fas fa-check"></i> Added';
                        
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                            cartCount.style.display = 'inline-block';
                        }
                        
                        // Reset button after delay
                        setTimeout(() => {
                            this.innerHTML = 'Add to Cart';
                            this.disabled = false;
                        }, 2000);
                    } else {
                        // Show error
                        this.innerHTML = 'Error';
                        this.disabled = false;
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = 'Error';
                    this.disabled = false;
                });
            });
        });
        
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
