<?php
/**
 * AI Product Recommendation System
 * 
 * This file contains functions for generating and managing product recommendations
 * using collaborative filtering and product similarity algorithms.
 */

// Function to generate recommendations based on product similarity
function generateProductSimilarityRecommendations() {
    global $conn;
    
    // Clear existing similarity recommendations
    $conn->query("DELETE FROM product_recommendations WHERE recommendation_type = 'similar'");
    
    // Get all products
    $products_query = $conn->query("SELECT id, name, description, category_id FROM products WHERE status = 'active'");
    $products = [];
    
    while ($product = $products_query->fetch_assoc()) {
        $products[] = $product;
    }
    
    // Calculate similarity between products
    for ($i = 0; $i < count($products); $i++) {
        $product1 = $products[$i];
        $recommendations = [];
        
        for ($j = 0; $j < count($products); $j++) {
            if ($i == $j) continue; // Skip same product
            
            $product2 = $products[$j];
            $score = calculateProductSimilarity($product1, $product2);
            
            if ($score > 0) {
                $recommendations[] = [
                    'product_id' => $product2['id'],
                    'score' => $score
                ];
            }
        }
        
        // Sort recommendations by score
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Take top 5 recommendations
        $recommendations = array_slice($recommendations, 0, 5);
        
        // Insert recommendations into database
        foreach ($recommendations as $rec) {
            $stmt = $conn->prepare("INSERT INTO product_recommendations 
                                   (product_id, recommended_product_id, score, recommendation_type) 
                                   VALUES (?, ?, ?, 'similar')");
            $stmt->bind_param("iid", $product1['id'], $rec['product_id'], $rec['score']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    return true;
}

// Calculate similarity between two products
function calculateProductSimilarity($product1, $product2) {
    $score = 0;
    
    // Same category gives higher similarity
    if ($product1['category_id'] == $product2['category_id']) {
        $score += 0.5;
    }
    
    // Text similarity in name and description
    $name_similarity = similar_text(strtolower($product1['name']), strtolower($product2['name']), $percent) / 100;
    $score += $name_similarity * 0.3;
    
    $desc_similarity = 0;
    if (!empty($product1['description']) && !empty($product2['description'])) {
        $desc_similarity = similar_text(strtolower($product1['description']), strtolower($product2['description']), $percent) / 100;
    }
    $score += $desc_similarity * 0.2;
    
    return $score;
}

// Generate frequently bought together recommendations based on order history
function generateFrequentlyBoughtTogetherRecommendations() {
    global $conn;
    
    // Clear existing recommendations
    $conn->query("DELETE FROM product_recommendations WHERE recommendation_type = 'frequently_bought_together'");
    
    // Find products frequently bought together
    $query = "
        SELECT oi1.product_id, oi2.product_id as recommended_product_id, COUNT(*) as frequency
        FROM order_items oi1
        JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id != oi2.product_id
        JOIN orders o ON oi1.order_id = o.id
        WHERE o.status IN ('delivered', 'shipped')
        GROUP BY oi1.product_id, oi2.product_id
        HAVING COUNT(*) > 1
        ORDER BY frequency DESC
    ";
    
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $score = min(1.0, $row['frequency'] / 10); // Normalize score between 0 and 1
        
        $stmt = $conn->prepare("INSERT INTO product_recommendations 
                               (product_id, recommended_product_id, score, recommendation_type) 
                               VALUES (?, ?, ?, 'frequently_bought_together')");
        $stmt->bind_param("iid", $row['product_id'], $row['recommended_product_id'], $score);
        $stmt->execute();
        $stmt->close();
    }
    
    return true;
}

// Generate "also viewed" recommendations based on user browsing behavior
function generateAlsoViewedRecommendations() {
    global $conn;
    
    // Clear existing recommendations
    $conn->query("DELETE FROM product_recommendations WHERE recommendation_type = 'also_viewed'");
    
    // Find products frequently viewed together in the same session
    $query = "
        SELECT upi1.product_id, upi2.product_id as recommended_product_id, COUNT(*) as frequency
        FROM user_product_interactions upi1
        JOIN user_product_interactions upi2 ON 
            (upi1.user_id = upi2.user_id OR upi1.session_id = upi2.session_id) 
            AND upi1.product_id != upi2.product_id
            AND upi1.interaction_type = 'view'
            AND upi2.interaction_type = 'view'
            AND ABS(TIMESTAMPDIFF(MINUTE, upi1.created_at, upi2.created_at)) < 30
        GROUP BY upi1.product_id, upi2.product_id
        HAVING COUNT(*) > 1
        ORDER BY frequency DESC
    ";
    
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $score = min(1.0, $row['frequency'] / 10); // Normalize score between 0 and 1
        
        $stmt = $conn->prepare("INSERT INTO product_recommendations 
                               (product_id, recommended_product_id, score, recommendation_type) 
                               VALUES (?, ?, ?, 'also_viewed')");
        $stmt->bind_param("iid", $row['product_id'], $row['recommended_product_id'], $score);
        $stmt->execute();
        $stmt->close();
    }
    
    return true;
}

// Track user interaction with products
function trackProductInteraction($product_id, $interaction_type) {
    global $conn;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    
    $stmt = $conn->prepare("INSERT INTO user_product_interactions 
                           (user_id, session_id, product_id, interaction_type) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $session_id, $product_id, $interaction_type);
    $stmt->execute();
    $stmt->close();
}

// Log recommendation display and clicks
function logRecommendationDisplay($product_id, $recommended_product_id, $recommendation_type) {
    global $conn;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    
    $stmt = $conn->prepare("INSERT INTO recommendation_logs 
                           (user_id, session_id, product_id, recommended_product_id, recommendation_type) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiis", $user_id, $session_id, $product_id, $recommended_product_id, $recommendation_type);
    $stmt->execute();
    $log_id = $stmt->insert_id;
    $stmt->close();
    
    return $log_id;
}

// Update recommendation log when clicked
function logRecommendationClick($log_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE recommendation_logs SET clicked = 1 WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $stmt->close();
}

// Update recommendation log when converted (added to cart or purchased)
function logRecommendationConversion($log_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE recommendation_logs SET converted = 1 WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $stmt->close();
}

// Get similar products for a given product
function getSimilarProducts($product_id, $limit = 4) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT p.*, pr.score
        FROM product_recommendations pr
        JOIN products p ON pr.recommended_product_id = p.id
        WHERE pr.product_id = ? 
        AND pr.recommendation_type = 'similar'
        AND p.status = 'active'
        ORDER BY pr.score DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

// Get frequently bought together products
function getFrequentlyBoughtTogether($product_id, $limit = 4) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT p.*, pr.score
        FROM product_recommendations pr
        JOIN products p ON pr.recommended_product_id = p.id
        WHERE pr.product_id = ? 
        AND pr.recommendation_type = 'frequently_bought_together'
        AND p.status = 'active'
        ORDER BY pr.score DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

// Get also viewed products
function getAlsoViewedProducts($product_id, $limit = 4) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT p.*, pr.score
        FROM product_recommendations pr
        JOIN products p ON pr.recommended_product_id = p.id
        WHERE pr.product_id = ? 
        AND pr.recommendation_type = 'also_viewed'
        AND p.status = 'active'
        ORDER BY pr.score DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

// Schedule daily recommendation generation
function scheduleRecommendationGeneration() {
    // Check if recommendations were generated today
    $last_generated = get_option('last_recommendation_generation', '');
    $today = date('Y-m-d');
    
    if ($last_generated != $today) {
        generateProductSimilarityRecommendations();
        generateFrequentlyBoughtTogetherRecommendations();
        generateAlsoViewedRecommendations();
        
        // Update last generation date
        update_option('last_recommendation_generation', $today);
    }
}

// Helper function to get option from site_settings
function get_option($key, $default = '') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return $default;
}

// Helper function to update option in site_settings
function update_option($key, $value) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO site_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
}

// Initialize recommendation system
function init_recommendation_system() {
    // Schedule daily recommendation generation
    scheduleRecommendationGeneration();
    
    // Add hooks for tracking user interactions
    // These would be called from appropriate places in the code
    // Example: add_action('view_product', 'track_product_view');
}

// Track product view
function track_product_view($product_id) {
    trackProductInteraction($product_id, 'view');
}

// Track add to cart
function track_add_to_cart($product_id) {
    trackProductInteraction($product_id, 'add_to_cart');
}

// Track purchase
function track_purchase($product_id) {
    trackProductInteraction($product_id, 'purchase');
}

// Track wishlist addition
function track_wishlist_add($product_id) {
    trackProductInteraction($product_id, 'wishlist');
}

// Initialize the recommendation system
init_recommendation_system();
