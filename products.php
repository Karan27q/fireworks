<?php
require_once 'includes/db_connect.php';
session_start();

$page_title = "Products";

// Get category filter if provided
$category_id = isset($_GET['category']) ? filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) : null;

// Get search query if provided
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : null;

// Get sort parameter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Pagination
$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.active = 1";

if ($category_id) {
    $query .= " AND p.category_id = " . intval($category_id);
}

if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
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
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

// Add pagination
$query .= " LIMIT $items_per_page OFFSET $offset";

// Execute query
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get total count for pagination
$count_query = str_replace("SELECT p.*, c.name as category_name", "SELECT COUNT(*)", $query);
$count_query = preg_replace("/LIMIT \d+ OFFSET \d+/", "", $count_query);

$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    die("Count query failed: " . mysqli_error($conn));
}

$total_items = mysqli_fetch_row($count_result)[0];
$total_pages = ceil($total_items / $items_per_page);

// Get all categories for filter
$categories_query = "SELECT * FROM categories WHERE active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

if (!$categories_result) {
    die("Categories query failed: " . mysqli_error($conn));
}

$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4"><?php echo $page_title; ?></h1>
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="products.php" method="get" class="d-flex">
                <?php if ($category_id): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="col-md-6 d-flex justify-content-end">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Categories
                </button>
                <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                    <li><a class="dropdown-item <?php echo !$category_id ? 'active' : ''; ?>" href="products.php">All Categories</a></li>
                    <?php foreach ($categories as $category): ?>
                        <li><a class="dropdown-item <?php echo $category_id == $category['id'] ? 'active' : ''; ?>" href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="dropdown ms-2">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Sort By
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item <?php echo $sort == 'newest' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>">Newest</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'price_low' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>">Price: Low to High</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'price_high' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>">Price: High to Low</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'name_asc' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_asc'])); ?>">Name: A to Z</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'name_desc' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_desc'])); ?>">Name: Z to A</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products found. Please try a different search or category.
        </div>
    <?php else: ?>
        <!-- Products Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <img src="<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'assets/images/product-placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-grid gap-2">
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Product pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Add to cart functionality
    $('.add-to-cart').click(function() {
        const productId = $(this).data('product-id');
        const button = $(this);
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        
        $.ajax({
            type: 'POST',
            url: 'ajax/add_to_cart.php',
            data: { product_id: productId, quantity: 1 },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    button.html('Added to Cart').removeClass('btn-primary').addClass('btn-success');
                    // Update cart count in header
                    $('#cart-count').text(response.cart_count);
                    
                    // Show toast notification
                    const toast = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="bi bi-check-circle me-2"></i> ${response.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    
                    $('.toast-container').append(toast);
                    const toastEl = $('.toast').last();
                    const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
                    bsToast.show();
                    
                    setTimeout(function() {
                        button.html('Add to Cart').removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                    }, 2000);
                } else {
                    button.html('Error').removeClass('btn-primary').addClass('btn-danger');
                    setTimeout(function() {
                        button.html('Add to Cart').removeClass('btn-danger').addClass('btn-primary').prop('disabled', false);
                    }, 2000);
                    
                    // Show error toast
                    const toast = `
                        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="bi bi-exclamation-circle me-2"></i> ${response.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    
                    $('.toast-container').append(toast);
                    const toastEl = $('.toast').last();
                    const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
                    bsToast.show();
                }
            },
            error: function() {
                button.html('Error').removeClass('btn-primary').addClass('btn-danger');
                setTimeout(function() {
                    button.html('Add to Cart').removeClass('btn-danger').addClass('btn-primary').prop('disabled', false);
                }, 2000);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
