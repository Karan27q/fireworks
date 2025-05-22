<?php
// Include database connection
include 'includes/db_connect.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$productId = $_GET['id'];

// Get product details
$query = "SELECT p.*, c.name as category_name, c.id as category_id, s.name as subcategory_name, s.id as subcategory_id 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN subcategories s ON p.subcategory_id = s.id 
          WHERE p.id = $productId AND p.active = 1";
$result = mysqli_query($conn, $query);

// Check if product exists
if (mysqli_num_rows($result) === 0) {
    header('Location: index.php');
    exit;
}

$product = mysqli_fetch_assoc($result);

// Set page title
$pageTitle = $product['name'];

// Get product images
$imagesQuery = "SELECT * FROM product_images WHERE product_id = $productId ORDER BY display_order ASC";
$imagesResult = mysqli_query($conn, $imagesQuery);
$productImages = [];

while ($image = mysqli_fetch_assoc($imagesResult)) {
    $productImages[] = $image;
}

// If no additional images, use main product image
if (empty($productImages)) {
    $productImages[] = [
        'image_path' => $product['image'],
        'alt_text' => $product['name']
    ];
}

// Get product attributes
$attributesQuery = "SELECT pa.*, a.name as attribute_name 
                   FROM product_attributes pa 
                   JOIN attributes a ON pa.attribute_id = a.id 
                   WHERE pa.product_id = $productId";
$attributesResult = mysqli_query($conn, $attributesQuery);
$productAttributes = [];

while ($attribute = mysqli_fetch_assoc($attributesResult)) {
    $productAttributes[] = $attribute;
}

// Get product reviews
$reviewsQuery = "SELECT r.*, u.name as user_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = $productId AND r.approved = 1 
                ORDER BY r.created_at DESC";
$reviewsResult = mysqli_query($conn, $reviewsQuery);
$productReviews = [];
$totalRating = 0;
$reviewCount = 0;

while ($review = mysqli_fetch_assoc($reviewsResult)) {
    $productReviews[] = $review;
    $totalRating += $review['rating'];
    $reviewCount++;
}

$averageRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0;

// Get related products
$relatedQuery = "SELECT * FROM products 
                WHERE (category_id = {$product['category_id']} OR subcategory_id = {$product['subcategory_id']}) 
                AND id != $productId AND active = 1 
                ORDER BY RAND() 
                LIMIT 4";
$relatedResult = mysqli_query($conn, $relatedQuery);
$relatedProducts = [];

while ($relatedProduct = mysqli_fetch_assoc($relatedResult)) {
    $relatedProducts[] = $relatedProduct;
}

// Get site options for customization
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="product-details-page">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a>
            <?php if($product['subcategory_name']): ?> &gt; 
            <a href="products.php?subcategory=<?php echo $product['subcategory_id']; ?>"><?php echo $product['subcategory_name']; ?></a>
            <?php endif; ?> &gt; 
            <span><?php echo $product['name']; ?></span>
        </div>
        
        <div class="product-details <?php echo $isMobile ? 'mobile-product-details' : ''; ?>">
            <!-- Product Images -->
            <div class="product-images">
                <?php if($isMobile): ?>
                <!-- Mobile Product Gallery -->
                <div class="mobile-product-gallery">
                    <div class="mobile-product-gallery-inner" id="mobileGallery">
                        <?php foreach($productImages as $index => $image): ?>
                        <div class="mobile-product-gallery-item">
                            <img src="uploads/products/<?php echo $image['image_path']; ?>" alt="<?php echo $image['alt_text']; ?>" class="lazy-image" data-src="uploads/products/<?php echo $image['image_path']; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mobile-product-gallery-dots">
                        <?php foreach($productImages as $index => $image): ?>
                        <div class="mobile-product-gallery-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Desktop Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image">
                        <img src="uploads/products/<?php echo $productImages[0]['image_path']; ?>" alt="<?php echo $productImages[0]['alt_text']; ?>" id="mainImage">
                    </div>
                    
                    <?php if(count($productImages) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach($productImages as $index => $image): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <img src="uploads/products/<?php echo $image['image_path']; ?>" alt="<?php echo $image['alt_text']; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info">
                <h1 class="product-title"><?php echo $product['name']; ?></h1>
                
                <?php if($reviewCount > 0): ?>
                <div class="product-rating">
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $averageRating): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif($i - 0.5 <= $averageRating): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count"><?php echo $reviewCount; ?> reviews</span>
                </div>
                <?php endif; ?>
                
                <div class="product-price">
                    <?php if($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                    <span class="regular-price">₹<?php echo number_format($product['price'], 2); ?></span>
                    <span class="sale-price">₹<?php echo number_format($product['sale_price'], 2); ?></span>
                    <span class="discount-badge">
                        <?php echo round(($product['price'] - $product['sale_price']) / $product['price'] * 100); ?>% OFF
                    </span>
                    <?php else: ?>
                    <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if(isset($settings['product_show_stock']) && $settings['product_show_stock']): ?>
                <div class="product-stock">
                    <?php if($product['stock_quantity'] > 0): ?>
                    <span class="in-stock">In Stock</span>
                    <?php else: ?>
                    <span class="out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="product-description">
                    <?php echo nl2br($product['description']); ?>
                </div>
                
                <?php if(!empty($productAttributes)): ?>
                <div class="product-attributes">
                    <h3>Specifications</h3>
                    <ul>
                        <?php foreach($productAttributes as $attribute): ?>
                        <li>
                            <span class="attribute-name"><?php echo $attribute['attribute_name']; ?>:</span>
                            <span class="attribute-value"><?php echo $attribute['value']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Add to Cart Form -->
                <form action="ajax/add_to_cart.php" method="POST" class="add-to-cart-form" id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <div class="quantity-input <?php echo $isMobile ? 'mobile' : ''; ?>">
                            <button type="button" class="quantity-decrease <?php echo $isMobile ? 'touch-friendly' : ''; ?>">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button type="button" class="quantity-increase <?php echo $isMobile ? 'touch-friendly' : ''; ?>">+</button>
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <button type="submit" class="btn btn-primary add-to-cart-btn <?php echo $isMobile ? 'mobile-btn' : ''; ?>" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        
                        <button type="button" class="btn btn-outline-primary buy-now-btn <?php echo $isMobile ? 'mobile-btn' : ''; ?>" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                    </div>
                </form>
                
                <!-- Product Meta -->
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">SKU:</span>
                        <span class="meta-value"><?php echo $product['sku']; ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label">Category:</span>
                        <span class="meta-value">
                            <a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a>
                            <?php if($product['subcategory_name']): ?>
                            , <a href="products.php?subcategory=<?php echo $product['subcategory_id']; ?>"><?php echo $product['subcategory_name']; ?></a>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if($product['tags']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Tags:</span>
                        <span class="meta-value">
                            <?php 
                            $tags = explode(',', $product['tags']);
                            foreach($tags as $index => $tag): 
                                $tag = trim($tag);
                                echo '<a href="search.php?q=' . urlencode($tag) . '">' . $tag . '</a>';
                                if($index < count($tags) - 1) echo ', ';
                            endforeach; 
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Social Sharing -->
                <div class="social-sharing">
                    <span>Share:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['name']); ?>" target="_blank" class="twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode($product['name'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="whatsapp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode($product['name']); ?>&body=<?php echo urlencode('Check out this product: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="product-tabs <?php echo $isMobile ? 'mobile-product-tabs' : ''; ?>">
            <?php if($isMobile): ?>
            <!-- Mobile Accordion Style Tabs -->
            <div class="mobile-accordion">
                <div class="mobile-accordion-item active">
                    <div class="mobile-accordion-header">
                        <h3>Description</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-accordion-content">
                        <?php echo nl2br($product['description']); ?>
                        
                        <?php if($product['features']): ?>
                        <h4>Features</h4>
                        <ul class="features-list">
                            <?php 
                            $features = explode("\n", $product['features']);
                            foreach($features as $feature): 
                                if(trim($feature) !== ''):
                            ?>
                            <li><?php echo trim($feature); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mobile-accordion-item">
                    <div class="mobile-accordion-header">
                        <h3>Specifications</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-accordion-content">
                        <?php if(!empty($productAttributes)): ?>
                        <ul class="specifications-list">
                            <?php foreach($productAttributes as $attribute): ?>
                            <li>
                                <span class="spec-name"><?php echo $attribute['attribute_name']; ?>:</span>
                                <span class="spec-value"><?php echo $attribute['value']; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No specifications available for this product.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mobile-accordion-item">
                    <div class="mobile-accordion-header">
                        <h3>Reviews (<?php echo $reviewCount; ?>)</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-accordion-content">
                        <?php if(!empty($productReviews)): ?>
                        <div class="reviews-summary">
                            <div class="average-rating">
                                <div class="rating-number"><?php echo $averageRating; ?></div>
                                <div class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $averageRating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif($i - 0.5 <= $averageRating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-count"><?php echo $reviewCount; ?> reviews</div>
                            </div>
                        </div>
                        
                        <div class="reviews-list">
                            <?php foreach($productReviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-name"><?php echo $review['user_name']; ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-content">
                                    <?php echo nl2br($review['review']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p>No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="write-review">
                            <h4>Write a Review</h4>
                            <form action="submit-review.php" method="POST" class="review-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="rating">Rating</label>
                                    <div class="rating-selector">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1"><i class="far fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="review">Your Review</label>
                                    <textarea id="review" name="review" class="form-control mobile-input" rows="4" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary mobile-btn">Submit Review</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="login-to-review">
                            <p>Please <a href="login.php">login</a> to write a review.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mobile-accordion-item">
                    <div class="mobile-accordion-header">
                        <h3>Shipping & Returns</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-accordion-content">
                        <h4>Shipping Information</h4>
                        <p>We ship all over India including North Eastern states. Shipping charges are calculated based on your location and order value.</p>
                        <ul>
                            <li>Free shipping on orders above ₹<?php echo number_format($settings['free_shipping_threshold'], 2); ?></li>
                            <li>Standard shipping: ₹<?php echo number_format($settings['base_shipping_cost'], 2); ?></li>
                            <li>Delivery time: 3-7 business days</li>
                        </ul>
                        
                        <h4>Return Policy</h4>
                        <p>Due to the nature of fireworks products, we have a strict return policy:</p>
                        <ul>
                            <li>Returns are only accepted for damaged or defective products</li>
                            <li>Returns must be initiated within 24 hours of delivery</li>
                            <li>Products must be unused and in original packaging</li>
                            <li>Contact our customer service team for return authorization</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Desktop Tabs -->
            <div class="tabs-container">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="description">Description</button>
                    <button class="tab-btn" data-tab="specifications">Specifications</button>
                    <button class="tab-btn" data-tab="reviews">Reviews (<?php echo $reviewCount; ?>)</button>
                    <button class="tab-btn" data-tab="shipping">Shipping & Returns</button>
                </div>
                
                <div class="tab-content">
                    <div class="tab-pane active" id="description">
                        <?php echo nl2br($product['description']); ?>
                        
                        <?php if($product['features']): ?>
                        <h3>Features</h3>
                        <ul class="features-list">
                            <?php 
                            $features = explode("\n", $product['features']);
                            foreach($features as $feature): 
                                if(trim($feature) !== ''):
                            ?>
                            <li><?php echo trim($feature); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane" id="specifications">
                        <?php if(!empty($productAttributes)): ?>
                        <table class="specifications-table">
                            <tbody>
                                <?php foreach($productAttributes as $attribute): ?>
                                <tr>
                                    <th><?php echo $attribute['attribute_name']; ?></th>
                                    <td><?php echo $attribute['value']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>No specifications available for this product.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane" id="reviews">
                        <?php if(!empty($productReviews)): ?>
                        <div class="reviews-summary">
                            <div class="average-rating">
                                <div class="rating-number"><?php echo $averageRating; ?></div>
                                <div class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $averageRating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif($i - 0.5 <= $averageRating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-count"><?php echo $reviewCount; ?> reviews</div>
                            </div>
                        </div>
                        
                        <div class="reviews-list">
                            <?php foreach($productReviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-name"><?php echo $review['user_name']; ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-content">
                                    <?php echo nl2br($review['review']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p>No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="write-review">
                            <h3>Write a Review</h3>
                            <form action="submit-review.php" method="POST" class="review-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="rating">Rating</label>
                                    <div class="rating-selector">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2"><i class="far fa-star"></i></label>
                                        
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1"><i class="far fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="review">Your Review</label>
                                    <textarea id="review" name="review" class="form-control" rows="4" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="login-to-review">
                            <p>Please <a href="login.php">login</a> to write a review.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane" id="shipping">
                        <h3>Shipping Information</h3>
                        <p>We ship all over India including North Eastern states. Shipping charges are calculated based on your location and order value.</p>
                        <ul>
                            <li>Free shipping on orders above ₹<?php echo number_format($settings['free_shipping_threshold'], 2); ?></li>
                            <li>Standard shipping: ₹<?php echo number_format($settings['base_shipping_cost'], 2); ?></li>
                            <li>Delivery time: 3-7 business days</li>
                        </ul>
                        
                        <h3>Return Policy</h3>
                        <p>Due to the nature of fireworks products, we have a strict return policy:</p>
                        <ul>
                            <li>Returns are only accepted for damaged or defective products</li>
                            <li>Returns must be initiated within 24 hours of delivery</li>
                            <li>Products must be unused and in original packaging</li>
                            <li>Contact our customer service team for return authorization</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Related Products -->
        <?php if(isset($settings['product_show_related']) && $settings['product_show_related'] && !empty($relatedProducts)): ?>
        <div class="related-products">
            <h2><?php echo isset($settings['product_related_title']) ? $settings['product_related_title'] : 'Related Products'; ?></h2>
            
            <div class="products-grid <?php echo $isMobile ? 'mobile-products-grid' : ''; ?>">
                <?php foreach($relatedProducts as $relatedProduct): ?>
                <div class="product-card <?php echo $isMobile ? 'mobile-card' : ''; ?>">
                    <a href="product-details.php?id=<?php echo $relatedProduct['id']; ?>" class="product-card-link">
                        <img src="uploads/products/<?php echo $relatedProduct['image']; ?>" alt="<?php echo $relatedProduct['name']; ?>" class="<?php echo $isMobile ? 'lazy-image' : ''; ?>" <?php echo $isMobile ? 'data-src="uploads/products/' . $relatedProduct['image'] . '"' : ''; ?>>
                        <h3><?php echo $relatedProduct['name']; ?></h3>
                        <p class="price">₹<?php echo $relatedProduct['price']; ?></p>
                    </a>
                    <div class="product-card-actions">
                        <a href="product-details.php?id=<?php echo $relatedProduct['id']; ?>" class="view-btn">View Details</a>
                        <button class="add-to-cart-btn" data-product-id="<?php echo $relatedProduct['id']; ?>">Add to Cart</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
        // Quantity buttons functionality
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.querySelector('.quantity-decrease');
        const increaseBtn = document.querySelector('.quantity-increase');
        
        if (quantityInput && decreaseBtn && increaseBtn) {
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                let max = parseInt(quantityInput.getAttribute('max'));
                
                if (value < max) {
                    quantityInput.value = value + 1;
                }
            });
        }
        
        // Desktop tabs functionality
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        if (tabButtons.length > 0) {
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        }
        
        // Mobile accordion functionality
        const accordionHeaders = document.querySelectorAll('.mobile-accordion-header');
        
        if (accordionHeaders.length > 0) {
            accordionHeaders.forEach(function(header) {
                header.addEventListener('click', function() {
                    const accordionItem = this.parentNode;
                    
                    // Toggle active class
                    accordionItem.classList.toggle('active');
                    
                    // Toggle icon
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-chevron-down');
                        icon.classList.toggle('fa-chevron-up');
                    }
                });
            });
        }
        
        // Desktop product gallery
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.getElementById('mainImage');
        
        if (thumbnails.length > 0 && mainImage) {
            thumbnails.forEach(function(thumbnail) {
                thumbnail.addEventListener('click', function() {
                    // Remove active class from all thumbnails
                    thumbnails.forEach(thumb => thumb.classList.remove('active'));
                    
                    // Add active class to clicked thumbnail
                    this.classList.add('active');
                    
                    // Update main image
                    const imgSrc = this.querySelector('img').getAttribute('src');
                    const imgAlt = this.querySelector('img').getAttribute('alt');
                    
                    mainImage.setAttribute('src', imgSrc);
                    mainImage.setAttribute('alt', imgAlt);
                });
            });
        }
        
        // Mobile product gallery
        const mobileGallery = document.getElementById('mobileGallery');
        const galleryDots = document.querySelectorAll('.mobile-product-gallery-dot');
        
        if (mobileGallery && galleryDots.length > 0) {
            let currentIndex = 0;
            const itemWidth = mobileGallery.querySelector('.mobile-product-gallery-item').offsetWidth;
            
            // Swipe functionality
            let touchStartX = 0;
            let touchEndX = 0;
            
            mobileGallery.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            mobileGallery.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const diff = touchStartX - touchEndX;
                
                if (diff > 50) {
                    // Swipe left - next image
                    if (currentIndex < galleryDots.length - 1) {
                        showSlide(currentIndex + 1);
                    }
                } else if (diff < -50) {
                    // Swipe right - previous image
                    if (currentIndex > 0) {
                        showSlide(currentIndex - 1);
                    }
                }
            }
            
            // Dot navigation
            galleryDots.forEach(function(dot, index) {
                dot.addEventListener('click', function() {
                    showSlide(index);
                });
            });
            
            function showSlide(index) {
                currentIndex = index;
                
                // Update gallery position
                mobileGallery.style.transform = `translateX(-${index * itemWidth}px)`;
                
                // Update dots
                galleryDots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }
        }
        
        // Rating selector functionality
        const ratingInputs = document.querySelectorAll('.rating-selector input');
        const ratingLabels = document.querySelectorAll('.rating-selector label');
        
        if (ratingInputs.length > 0 && ratingLabels.length > 0) {
            ratingInputs.forEach(function(input, index) {
                input.addEventListener('change', function() {
                    const rating = this.value;
                    
                    // Update stars
                    ratingLabels.forEach(function(label, i) {
                        const star = label.querySelector('i');
                        if (i < rating) {
                            star.classList.remove('far');
                            star.classList.add('fas');
                        } else {
                            star.classList.remove('fas');
                            star.classList.add('far');
                        }
                    });
                });
            });
            
            // Hover effect
            ratingLabels.forEach(function(label, index) {
                label.addEventListener('mouseenter', function() {
                    const stars = Array.from(ratingLabels).slice(0, index + 1);
                    
                    stars.forEach(function(star) {
                        const icon = star.querySelector('i');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    });
                });
                
                label.addEventListener('mouseleave', function() {
                    const checkedInput = document.querySelector('.rating-selector input:checked');
                    const rating = checkedInput ? parseInt(checkedInput.value) : 0;
                    
                    ratingLabels.forEach(function(label, i) {
                        const star = label.querySelector('i');
                        if (i < rating) {
                            star.classList.remove('far');
                            star.classList.add('fas');
                        } else {
                            star.classList.remove('fas');
                            star.classList.add('far');
                        }
                    });
                });
            });
        }
        
        // Add to cart functionality
        const addToCartForm = document.getElementById('addToCartForm');
        
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('.add-to-cart-btn');
                
                // Show loading
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                submitButton.disabled = true;
                
                // Send AJAX request
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        submitButton.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                        
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                            cartCount.style.display = 'inline-block';
                        }
                        
                        // Reset button after delay
                        setTimeout(() => {
                            submitButton.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                            submitButton.disabled = false;
                        }, 2000);
                    } else {
                        // Show error
                        submitButton.innerHTML = 'Error';
                        submitButton.disabled = false;
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitButton.innerHTML = 'Error';
                    submitButton.disabled = false;
                });
            });
        }
        
        // Buy now functionality
        const buyNowBtn = document.querySelector('.buy-now-btn');
        
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                const formData = new FormData(addToCartForm);
                
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Add to cart first
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to checkout
                        window.location.href = 'checkout.php';
                    } else {
                        // Show error
                        this.innerHTML = '<i class="fas fa-bolt"></i> Buy Now';
                        this.disabled = false;
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = '<i class="fas fa-bolt"></i> Buy Now';
                    this.disabled = false;
                });
            });
        }
        
        // Add to cart functionality for related products
        const relatedAddToCartButtons = document.querySelectorAll('.products-grid .add-to-cart-btn');
        
        relatedAddToCartButtons.forEach(function(button) {
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
    });
</script>
