<?php
/**
 * Mobile Bottom Navigation for Vamsi Crackers E-commerce Platform
 * This file is included in the footer.php for mobile devices only
 */
?>

<div class="mobile-bottom-nav">
    <div class="mobile-bottom-nav-item" data-href="index.php">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </div>
    <div class="mobile-bottom-nav-item" data-href="categories.php">
        <i class="fas fa-th-large"></i>
        <span>Categories</span>
    </div>
    <div class="mobile-bottom-nav-item" data-href="cart.php">
        <i class="fas fa-shopping-cart"></i>
        <span>Cart</span>
        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
        <?php endif; ?>
    </div>
    <div class="mobile-bottom-nav-item" data-href="search.php">
        <i class="fas fa-search"></i>
        <span>Search</span>
    </div>
    <div class="mobile-bottom-nav-item" data-href="<?php echo isset($_SESSION['user_id']) ? 'my-account.php' : 'login.php'; ?>">
        <i class="fas fa-user"></i>
        <span><?php echo isset($_SESSION['user_id']) ? 'Account' : 'Login'; ?></span>
    </div>
</div>

<!-- Mobile-specific modals and overlays -->
<div class="mobile-menu-overlay"></div>

<!-- Mobile search overlay -->
<div class="mobile-search-overlay">
    <div class="mobile-search-container">
        <div class="mobile-search-header">
            <h3>Search Products</h3>
            <button class="mobile-search-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="mobile-search-form">
            <form action="search.php" method="get">
                <div class="form-group">
                    <input type="text" name="q" class="form-control mobile-input" placeholder="Search for products..." autocomplete="off">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="mobile-search-popular">
            <h4>Popular Searches</h4>
            <div class="mobile-search-tags">
                <?php
                // Get popular search terms from database
                $popular_searches = array('Sparklers', 'Rockets', 'Fountains', 'Gift Boxes', 'Kids Special');
                foreach($popular_searches as $term):
                ?>
                <a href="search.php?q=<?php echo urlencode($term); ?>" class="mobile-search-tag"><?php echo $term; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mobile filter sheet -->
<div class="mobile-bottom-sheet" id="filterSheet">
    <div class="mobile-bottom-sheet-header">
        <div class="mobile-bottom-sheet-title">Filter Products</div>
        <button class="mobile-bottom-sheet-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-bottom-sheet-content">
        <div class="mobile-filter-options">
            <div class="mobile-filter-option">
                <div class="mobile-filter-option-header">
                    <div class="mobile-filter-option-title">Price Range</div>
                    <div class="mobile-filter-option-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="mobile-filter-option-content">
                    <div class="price-range-slider">
                        <input type="range" min="0" max="5000" value="0" class="price-range-min">
                        <input type="range" min="0" max="5000" value="5000" class="price-range-max">
                    </div>
                    <div class="price-range-values">
                        <span class="price-range-value-min">₹0</span>
                        <span class="price-range-value-max">₹5000</span>
                    </div>
                </div>
            </div>
            <div class="mobile-filter-option">
                <div class="mobile-filter-option-header">
                    <div class="mobile-filter-option-title">Categories</div>
                    <div class="mobile-filter-option-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="mobile-filter-option-content">
                    <div class="checkbox-list">
                        <?php
                        // Get categories from database
                        $categories = array('Sparklers', 'Rockets', 'Fountains', 'Ground Chakkar', 'Aerial Shots');
                        foreach($categories as $category):
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="cat_<?php echo strtolower(str_replace(' ', '_', $category)); ?>" name="category[]" value="<?php echo $category; ?>">
                            <label for="cat_<?php echo strtolower(str_replace(' ', '_', $category)); ?>"><?php echo $category; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="mobile-filter-option">
                <div class="mobile-filter-option-header">
                    <div class="mobile-filter-option-title">Sort By</div>
                    <div class="mobile-filter-option-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="mobile-filter-option-content">
                    <div class="radio-list">
                        <div class="radio-item">
                            <input type="radio" id="sort_popular" name="sort" value="popular" checked>
                            <label for="sort_popular">Popularity</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="sort_price_low" name="sort" value="price_low">
                            <label for="sort_price_low">Price: Low to High</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="sort_price_high" name="sort" value="price_high">
                            <label for="sort_price_high">Price: High to Low</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="sort_newest" name="sort" value="newest">
                            <label for="sort_newest">Newest First</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="filter-actions">
            <button class="btn btn-outline-secondary mobile-btn" id="resetFilters">Reset</button>
            <button class="btn btn-primary mobile-btn" id="applyFilters">Apply Filters</button>
        </div>
    </div>
</div>

<!-- Mobile toast notification -->
<div class="mobile-toast" id="mobileToast"></div>

<!-- Offline indicator -->
<div class="offline-indicator">
    You are currently offline. Some features may not be available.
</div>

<script>
    // Initialize mobile-specific functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile search functionality
        const searchToggle = document.querySelector('.mobile-bottom-nav-item[data-href="search.php"]');
        const searchOverlay = document.querySelector('.mobile-search-overlay');
        const searchClose = document.querySelector('.mobile-search-close');
        
        if (searchToggle && searchOverlay) {
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                searchOverlay.classList.add('active');
                document.body.classList.add('overlay-open');
                setTimeout(function() {
                    searchOverlay.querySelector('input').focus();
                }, 300);
            });
            
            if (searchClose) {
                searchClose.addEventListener('click', function() {
                    searchOverlay.classList.remove('active');
                    document.body.classList.remove('overlay-open');
                });
            }
        }
        
        // Mobile filter functionality
        const filterButton = document.querySelector('.mobile-filter-button');
        const filterSheet = document.getElementById('filterSheet');
        const filterClose = document.querySelector('.mobile-bottom-sheet-close');
        
        if (filterButton && filterSheet) {
            filterButton.addEventListener('click', function() {
                filterSheet.classList.add('active');
                document.body.classList.add('overlay-open');
            });
            
            if (filterClose) {
                filterClose.addEventListener('click', function() {
                    filterSheet.classList.remove('active');
                    document.body.classList.remove('overlay-open');
                });
            }
        }
        
        // Filter option toggles
        const filterOptions = document.querySelectorAll('.mobile-filter-option');
        
        filterOptions.forEach(function(option) {
            const header = option.querySelector('.mobile-filter-option-header');
            
            if (header) {
                header.addEventListener('click', function() {
                    option.classList.toggle('active');
                    
                    const toggle = this.querySelector('.mobile-filter-option-toggle i');
                    if (toggle) {
                        toggle.classList.toggle('fa-chevron-down');
                        toggle.classList.toggle('fa-chevron-up');
                    }
                });
            }
        });
        
        // Price range slider
        const priceRangeMin = document.querySelector('.price-range-min');
        const priceRangeMax = document.querySelector('.price-range-max');
        const priceValueMin = document.querySelector('.price-range-value-min');
        const priceValueMax = document.querySelector('.price-range-value-max');
        
        if (priceRangeMin && priceRangeMax && priceValueMin && priceValueMax) {
            priceRangeMin.addEventListener('input', function() {
                priceValueMin.textContent = '₹' + this.value;
                
                // Ensure min doesn't exceed max
                if (parseInt(this.value) > parseInt(priceRangeMax.value)) {
                    priceRangeMax.value = this.value;
                    priceValueMax.textContent = '₹' + this.value;
                }
            });
            
            priceRangeMax.addEventListener('input', function() {
                priceValueMax.textContent = '₹' + this.value;
                
                // Ensure max doesn't go below min
                if (parseInt(this.value) < parseInt(priceRangeMin.value)) {
                    priceRangeMin.value = this.value;
                    priceValueMin.textContent = '₹' + this.value;
                }
            });
        }
        
        // Reset filters
        const resetFilters = document.getElementById('resetFilters');
        
        if (resetFilters) {
            resetFilters.addEventListener('click', function() {
                // Reset checkboxes
                document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                
                // Reset radio buttons
                document.querySelector('input[id="sort_popular"]').checked = true;
                
                // Reset price range
                if (priceRangeMin && priceRangeMax && priceValueMin && priceValueMax) {
                    priceRangeMin.value = 0;
                    priceRangeMax.value = 5000;
                    priceValueMin.textContent = '₹0';
                    priceValueMax.textContent = '₹5000';
                }
                
                // Show toast notification
                showMobileToast('Filters reset');
            });
        }
        
        // Apply filters
        const applyFilters = document.getElementById('applyFilters');
        
        if (applyFilters) {
            applyFilters.addEventListener('click', function() {
                // Close filter sheet
                filterSheet.classList.remove('active');
                document.body.classList.remove('overlay-open');
                
                // Show loading indicator
                showMobileLoading();
                
                // In a real implementation, you would collect filter values and submit them
                // For demo purposes, just show a toast after a delay
                setTimeout(function() {
                    hideMobileLoading();
                    showMobileToast('Filters applied');
                    
                    // Reload products (in a real implementation)
                    // reloadProducts(filterData);
                }, 1000);
            });
        }
        
        // Offline detection
        const offlineIndicator = document.querySelector('.offline-indicator');
        
        if (offlineIndicator) {
            window.addEventListener('online', function() {
                offlineIndicator.classList.remove('active');
            });
            
            window.addEventListener('offline', function() {
                offlineIndicator.classList.add('active');
            });
            
            // Check initial state
            if (!navigator.onLine) {
                offlineIndicator.classList.add('active');
            }
        }
        
        // Pull to refresh functionality (for product listings)
        setupPullToRefresh();
    });
    
    // Show mobile toast notification
    function showMobileToast(message) {
        const toast = document.getElementById('mobileToast');
        
        if (toast) {
            toast.textContent = message;
            toast.classList.add('active');
            
            setTimeout(function() {
                toast.classList.remove('active');
            }, 3000);
        }
    }
    
    // Show mobile loading indicator
    function showMobileLoading() {
        let loading = document.querySelector('.mobile-loading');
        
        if (!loading) {
            loading = document.createElement('div');
            loading.className = 'mobile-loading';
            loading.innerHTML = '<div class="mobile-loading-spinner"></div>';
            document.body.appendChild(loading);
        }
        
        loading.classList.add('active');
    }
    
    // Hide mobile loading indicator
    function hideMobileLoading() {
        const loading = document.querySelector('.mobile-loading');
        
        if (loading) {
            loading.classList.remove('active');
        }
    }
    
    // Setup pull to refresh
    function setupPullToRefresh() {
        const productListings = document.querySelector('.product-listings');
        
        if (productListings) {
            let touchStartY = 0;
            let touchEndY = 0;
            const minSwipeDistance = 80;
            
            // Add pull to refresh indicator
            const indicator = document.createElement('div');
            indicator.className = 'pull-to-refresh-indicator';
            indicator.innerHTML = '<div class="pull-to-refresh-spinner"></div>';
            productListings.parentNode.insertBefore(indicator, productListings);
            
            // Convert to pull to refresh container
            productListings.parentNode.classList.add('pull-to-refresh');
            
            productListings.addEventListener('touchstart', function(e) {
                touchStartY = e.touches[0].clientY;
            });
            
            productListings.addEventListener('touchmove', function(e) {
                if (window.scrollY === 0) {
                    touchEndY = e.touches[0].clientY;
                    const distance = touchEndY - touchStartY;
                    
                    if (distance > 0 && distance <= minSwipeDistance) {
                        indicator.style.transform = `translateY(${distance - 50 + distance * 0.2}px)`;
                        e.preventDefault();
                    }
                }
            });
            
            productListings.addEventListener('touchend', function() {
                const distance = touchEndY - touchStartY;
                
                if (window.scrollY === 0 && distance > minSwipeDistance) {
                    // Show full loading indicator
                    indicator.style.transform = 'translateY(0)';
                    
                    // Add loading animation
                    indicator.querySelector('.pull-to-refresh-spinner').style.display = 'block';
                    
                    // In a real implementation, you would reload data here
                    setTimeout(function() {
                        // Hide indicator
                        indicator.style.transform = 'translateY(-50px)';
                        
                        // Show toast
                        showMobileToast('Products refreshed');
                        
                        // Reload page or fetch new data
                        // location.reload();
                    }, 1500);
                } else {
                    // Reset indicator position
                    indicator.style.transform = 'translateY(-50px)';
                }
            });
        }
    }
</script>
