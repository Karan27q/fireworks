<?php
require_once 'includes/db_connect.php';
session_start();

$page_title = "Products";

// Get category filter if provided
$category_id = isset($_GET['category']) ? filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) : null;
$subcategory_id = isset($_GET['subcategory']) ? filter_input(INPUT_GET, 'subcategory', FILTER_VALIDATE_INT) : null;

// Get search query if provided
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : null;

// Get sort parameter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Pagination
$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN subcategories s ON p.subcategory_id = s.id 
          WHERE p.active = 1";

if ($category_id) {
    $query .= " AND p.category_id = " . intval($category_id);
}

if ($subcategory_id) {
    $query .= " AND p.subcategory_id = " . intval($subcategory_id);
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
$count_query = str_replace("SELECT p.*, c.name as category_name, s.name as subcategory_name", "SELECT COUNT(*)", $query);
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

// Get subcategories if category is selected
$subcategories = [];
if ($category_id) {
    $subcategories_query = "SELECT * FROM subcategories WHERE category_id = " . intval($category_id) . " AND active = 1 ORDER BY name";
    $subcategories_result = mysqli_query($conn, $subcategories_query);
    if ($subcategories_result) {
        $subcategories = mysqli_fetch_all($subcategories_result, MYSQLI_ASSOC);
    }
}

include 'includes/header.php';
?>

<div class="products-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / Products
                <?php if ($category_id): ?>
                    / <?php echo htmlspecialchars($categories[array_search($category_id, array_column($categories, 'id'))]['name']); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="row">
                <div class="col-md-4">
                    <form action="" method="get" class="search-form">
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="category-filter">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sort-filter">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                No products found. Please try a different search or category.
            </div>
        <?php else: ?>
            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
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
                                <?php if(!empty($product['subcategory_name'])): ?>
                                    <span class="subcategory">/ <?php echo htmlspecialchars($product['subcategory_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-price">
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $queryParams = [];
                    if($category_id) $queryParams[] = "category=$category_id";
                    if($subcategory_id) $queryParams[] = "subcategory=$subcategory_id";
                    if($search) $queryParams[] = "search=" . urlencode($search);
                    if($sort) $queryParams[] = "sort=$sort";
                    
                    // Previous page link
                    if($page > 1) {
                        $queryParams[] = "page=" . ($page - 1);
                        echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">&laquo; Previous</a>';
                    }
                    
                    // Page links
                    $startPage = max(1, $page - 2);
                    $endPage = min($total_pages, $page + 2);
                    
                    for($i = $startPage; $i <= $endPage; $i++) {
                        $queryParams = [];
                        if($category_id) $queryParams[] = "category=$category_id";
                        if($subcategory_id) $queryParams[] = "subcategory=$subcategory_id";
                        if($search) $queryParams[] = "search=" . urlencode($search);
                        if($sort) $queryParams[] = "sort=$sort";
                        $queryParams[] = "page=$i";
                        
                        if($i == $page) {
                            echo '<span class="pagination-link active">' . $i . '</span>';
                        } else {
                            echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">' . $i . '</a>';
                        }
                    }
                    
                    // Next page link
                    if($page < $total_pages) {
                        $queryParams = [];
                        if($category_id) $queryParams[] = "category=$category_id";
                        if($subcategory_id) $queryParams[] = "subcategory=$subcategory_id";
                        if($search) $queryParams[] = "search=" . urlencode($search);
                        if($sort) $queryParams[] = "sort=$sort";
                        $queryParams[] = "page=" . ($page + 1);
                        echo '<a href="?' . implode('&', $queryParams) . '" class="pagination-link">Next &raquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.products-page {
    padding: 40px 0;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 10px;
}

.breadcrumb {
    color: #666;
}

.breadcrumb a {
    color: #007bff;
    text-decoration: none;
}

.filters-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.search-form .input-group {
    width: 100%;
}

.search-form .form-control {
    border-right: none;
}

.search-form .btn {
    border-left: none;
}

.category-filter,
.sort-filter {
    width: 100%;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
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
    padding: 20px;
}

.product-title {
    font-size: 1.1rem;
    margin-bottom: 10px;
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
    margin-bottom: 10px;
}

.subcategory {
    color: #999;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 600;
    color: #28a745;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 40px;
}

.pagination-link {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-link:hover {
    background-color: #f8f9fa;
}

.pagination-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .filters-section .row {
        gap: 15px;
    }
    
    .filters-section .col-md-4 {
        width: 100%;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
