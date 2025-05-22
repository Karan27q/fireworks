<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <a href="index.php">Dashboard</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php' || basename($_SERVER['PHP_SELF']) == 'view-order.php') ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <a href="orders.php">Orders</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'add-product.php' || basename($_SERVER['PHP_SELF']) == 'edit-product.php') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <a href="products.php">Products</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php' || basename($_SERVER['PHP_SELF']) == 'add-category.php' || basename($_SERVER['PHP_SELF']) == 'edit-category.php') ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            <a href="categories.php">Categories</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'subcategories.php' || basename($_SERVER['PHP_SELF']) == 'add-subcategory.php' || basename($_SERVER['PHP_SELF']) == 'edit-subcategory.php') ? 'active' : ''; ?>">
            <i class="fas fa-tag"></i>
            <a href="subcategories.php">Subcategories</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'discounts.php' || basename($_SERVER['PHP_SELF']) == 'add-discount.php' || basename($_SERVER['PHP_SELF']) == 'edit-discount.php') ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <a href="discounts.php">Discounts</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'view-user.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <a href="users.php">Users</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'banners.php' || basename($_SERVER['PHP_SELF']) == 'add-banner.php' || basename($_SERVER['PHP_SELF']) == 'edit-banner.php') ? 'active' : ''; ?>">
            <i class="fas fa-image"></i>
            <a href="banners.php">Banners</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'testimonials.php' || basename($_SERVER['PHP_SELF']) == 'add-testimonial.php' || basename($_SERVER['PHP_SELF']) == 'edit-testimonial.php') ? 'active' : ''; ?>">
            <i class="fas fa-quote-left"></i>
            <a href="testimonials.php">Testimonials</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <a href="reports.php">Reports</a>
        </div>

        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'site-customization.php') ? 'active' : ''; ?>">
            <i class="fas fa-paint-brush"></i>
            <a href="site-customization.php">Site Customization</a>
        </div>

        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'check-server.php') ? 'active' : ''; ?>">
            <i class="fas fa-server"></i>
            <a href="check-server.php">Server Check</a>
        </div>
        
        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <a href="settings.php">Settings</a>
        </div>

        <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'payment-settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <a href="payment-settings.php">Payment Settings</a>
        </div>

        <div class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
</aside>
