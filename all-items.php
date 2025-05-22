<?php
// Include database connection
include 'includes/db_connect.php';

// Set page title
$pageTitle = "All Products";

// Get category filter if provided
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get subcategory filter if provided
$subcategory_id = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : 0;

// Get sort parameter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN subcategories s ON p.subcategory_id = s.id 
          WHERE p.active = 1";

// Add filters
if ($category_id > 0) {
    $query .= " AND p.category_id = $category_id";
}

if ($subcategory_id > 0) {
    $query .= " AND p.subcategory_id = $subcategory_id";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.id ASC, p.name ASC";
        break;
}

// Add pagination
$query .= " LIMIT $items_per_page OFFSET $offset";

// Execute query
$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    // Handle query error - display an error message and stop execution
    die("Error fetching products: " . mysqli_error($conn));
}

$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p WHERE p.active = 1";
if ($category_id > 0) {
    $count_query .= " AND p.category_id = $category_id";
}
if ($subcategory_id > 0) {
    $count_query .= " AND p.subcategory_id = $subcategory_id";
}
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_items = $count_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get all categories for filter
$categories_query = "SELECT * FROM categories WHERE active = 1 ORDER BY display_order ASC, name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// Get subcategories if category is selected
$subcategories = [];
if ($category_id > 0) {
    $subcategories_query = "SELECT * FROM subcategories WHERE category_id = $category_id AND active = 1 ORDER BY display_order ASC, name ASC";
    $subcategories_result = mysqli_query($conn, $subcategories_query);
    $subcategories = mysqli_fetch_all($subcategories_result, MYSQLI_ASSOC);
}

// Check if mobile view
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="products-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $pageTitle; ?></h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; 
                <?php if ($category_id > 0): ?>
                    <a href="all-items.php">All Products</a> &gt; 
                    <?php 
                    $category_name = '';
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $category_id) {
                            $category_name = $cat['name'];
                            break;
                        }
                    }
                    ?>
                    <span><?php echo $category_name; ?></span>
                <?php else: ?>
                    <span>All Products</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="products-container">
            <!-- Filters and Sorting -->
            <div class="filters-section <?php echo $isMobile ? 'mobile-filters' : ''; ?>">
                <?php if ($isMobile): ?>
                <div class="mobile-filter-toggle">
                    <button class="btn btn-outline-primary btn-block" id="filterToggle">
                        <i class="fas fa-filter"></i> Filter & Sort
                    </button>
                </div>
                
                <div class="mobile-filter-panel" id="filterPanel">
                    <div class="mobile-filter-header">
                        <h3>Filter & Sort</h3>
                        <button class="close-filter" id="closeFilter">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mobile-filter-content">
                        <div class="filter-group">
                            <h4>Categories</h4>
                            <ul class="filter-list">
                                <li>
                                    <a href="all-items.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">All Categories</a>
                                </li>
                                <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="all-items.php?category=<?php echo $category['id']; ?>" class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                        <?php echo $category['name']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <?php if (!empty($subcategories)): ?>
                        <div class="filter-group">
                            <h4>Subcategories</h4>
                            <ul class="filter-list">
                                <li>
                                    <a href="all-items.php?category=<?php echo $category_id; ?>" class="<?php echo $subcategory_id == 0 ? 'active' : ''; ?>">All Subcategories</a>
                                </li>
                                <?php foreach ($subcategories as $subcategory): ?>
                                <li>
                                    <a href="all-items.php?category=<?php echo $category_id; ?>&subcategory=<?php echo $subcategory['id']; ?>" class="<?php echo $subcategory_id == $subcategory['id'] ? 'active' : ''; ?>">
                                        <?php echo $subcategory['name']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <h4>Sort By</h4>
                            <ul class="filter-list">
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'default'); ?>" class="<?php echo $sort == 'default' ? 'active' : ''; ?>">Default</a>
                                </li>
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'price_low'); ?>" class="<?php echo $sort == 'price_low' ? 'active' : ''; ?>">Price: Low to High</a>
                                </li>
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'price_high'); ?>" class="<?php echo $sort == 'price_high' ? 'active' : ''; ?>">Price: High to Low</a>
                                </li>
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'name_asc'); ?>" class="<?php echo $sort == 'name_asc' ? 'active' : ''; ?>">Name: A to Z</a>
                                </li>
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'name_desc'); ?>" class="<?php echo $sort == 'name_desc' ? 'active' : ''; ?>">Name: Z to A</a>
                                </li>
                                <li>
                                    <a href="<?php echo add_query_arg('sort', 'newest'); ?>" class="<?php echo $sort == 'newest' ? 'active' : ''; ?>">Newest First</a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mobile-filter-actions">
                            <button class="btn btn-primary btn-block" id="applyFilters">Apply Filters</button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="desktop-filters">
                    <div class="filter-card">
                        <h3>Categories</h3>
                        <ul class="filter-list">
                            <li>
                                <a href="all-items.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">All Categories</a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="all-items.php?category=<?php echo $category['id']; ?>" class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                    <?php echo $category['name']; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($subcategories)): ?>
                    <div class="filter-card">
                        <h3>Subcategories</h3>
                        <ul class="filter-list">
                            <li>
                                <a href="all-items.php?category=<?php echo $category_id; ?>" class="<?php echo $subcategory_id == 0 ? 'active' : ''; ?>">All Subcategories</a>
                            </li>
                            <?php foreach ($subcategories as $subcategory): ?>
                            <li>
                                <a href="all-items.php?category=<?php echo $category_id; ?>&subcategory=<?php echo $subcategory['id']; ?>" class="<?php echo $subcategory_id == $subcategory['id'] ? 'active' : ''; ?>">
                                    <?php echo $subcategory['name']; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-card">
                        <h3>Sort By</h3>
                        <ul class="filter-list">
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'default'); ?>" class="<?php echo $sort == 'default' ? 'active' : ''; ?>">Default</a>
                            </li>
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'price_low'); ?>" class="<?php echo $sort == 'price_low' ? 'active' : ''; ?>">Price: Low to High</a>
                            </li>
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'price_high'); ?>" class="<?php echo $sort == 'price_high' ? 'active' : ''; ?>">Price: High to Low</a>
                            </li>
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'name_asc'); ?>" class="<?php echo $sort == 'name_asc' ? 'active' : ''; ?>">Name: A to Z</a>
                            </li>
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'name_desc'); ?>" class="<?php echo $sort == 'name_desc' ? 'active' : ''; ?>">Name: Z to A</a>
                            </li>
                            <li>
                                <a href="<?php echo add_query_arg('sort', 'newest'); ?>" class="<?php echo $sort == 'newest' ? 'active' : ''; ?>">Newest First</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Products Display -->
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <p>No products found in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="/uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo $product['name']; ?></h3>
                                <p class="product-category"><?php echo $product['category_name']; ?></p>
                                <?php if ($product['subcategory_name']): ?>
                                    <p class="product-subcategory"><?php echo $product['subcategory_name']; ?></p>
                                <?php endif; ?>
                                <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-stock">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span class="in-stock">In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                    <?php else: ?>
                                        <span class="out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-secondary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                            Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $subcategory_id ? '&subcategory=' . $subcategory_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $subcategory_id ? '&subcategory=' . $subcategory_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $subcategory_id ? '&subcategory=' . $subcategory_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
        // Mobile filter toggle
        const filterToggle = document.getElementById('filterToggle');
        const filterPanel = document.getElementById('filterPanel');
        const closeFilter = document.getElementById('closeFilter');
        const applyFilters = document.getElementById('applyFilters');
        
        if (filterToggle && filterPanel && closeFilter) {
            filterToggle.addEventListener('click', function() {
                filterPanel.classList.add('active');
                document.body.classList.add('filter-open');
            });
            
            closeFilter.addEventListener('click', function() {
                filterPanel.classList.remove('active');
                document.body.classList.remove('filter-open');
            });
            
            if (applyFilters) {
                applyFilters.addEventListener('click', function() {
                    filterPanel.classList.remove('active');
                    document.body.classList.remove('filter-open');
                });
            }
        }
        
        // Sort dropdown functionality
        const sortSelect = document.getElementById('sort-select');
        
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('sort', this.value);
                window.location.href = currentUrl.toString();
            });
        }
        
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        
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
    
    // Helper function to add query parameters
    function add_query_arg(key, value) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set(key, value);
        return currentUrl.toString();
    }
</script>
