<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include '../includes/db_connect.php';

// Get admin information
$adminId = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admins WHERE id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Get available languages
$languagesQuery = "SELECT * FROM languages ORDER BY is_default DESC, name ASC";
$languagesResult = mysqli_query($conn, $languagesQuery);

if(!$languagesResult || mysqli_num_rows($languagesResult) === 0) {
    // Create default languages if they don't exist
    $defaultLanguages = [
        ['code' => 'en', 'name' => 'English', 'is_default' => 1, 'active' => 1],
        ['code' => 'hi', 'name' => 'Hindi', 'is_default' => 0, 'active' => 1],
        ['code' => 'te', 'name' => 'Telugu', 'is_default' => 0, 'active' => 1]
    ];
    
    foreach($defaultLanguages as $lang) {
        $insertQuery = "INSERT INTO languages (code, name, is_default, active) 
                       VALUES ('{$lang['code']}', '{$lang['name']}', {$lang['is_default']}, {$lang['active']})";
        mysqli_query($conn, $insertQuery);
    }
    
    // Refresh languages
    $languagesResult = mysqli_query($conn, $languagesQuery);
}

$languages = mysqli_fetch_all($languagesResult, MYSQLI_ASSOC);

// Handle language creation
if(isset($_POST['create_language'])) {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Check if language code already exists
    $checkQuery = "SELECT id FROM languages WHERE code = '$code'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if(mysqli_num_rows($checkResult) > 0) {
        $errorMessage = "Language code '$code' already exists";
    } else {
        $insertQuery = "INSERT INTO languages (code, name, is_default, active) 
                       VALUES ('$code', '$name', 0, $active)";
        $insertResult = mysqli_query($conn, $insertQuery);
        
        if($insertResult) {
            $successMessage = "Language created successfully";
            
            // Refresh languages
            $languagesResult = mysqli_query($conn, $languagesQuery);
            $languages = mysqli_fetch_all($languagesResult, MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to create language: " . mysqli_error($conn);
        }
    }
}

// Handle language update
if(isset($_POST['update_language'])) {
    $languageId = (int)$_POST['language_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $active = isset($_POST['active']) ? 1 : 0;
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // If setting as default, unset current default
        if($isDefault) {
            $updateDefaultQuery = "UPDATE languages SET is_default = 0";
            $updateDefaultResult = mysqli_query($conn, $updateDefaultQuery);
            
            if(!$updateDefaultResult) {
                throw new Exception("Failed to update default language: " . mysqli_error($conn));
            }
        }
        
        // Update language
        $updateQuery = "UPDATE languages SET 
                       name = '$name',
                       active = $active,
                       is_default = $isDefault
                       WHERE id = $languageId";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if(!$updateResult) {
            throw new Exception("Failed to update language: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $successMessage = "Language updated successfully";
        
        // Refresh languages
        $languagesResult = mysqli_query($conn, $languagesQuery);
        $languages = mysqli_fetch_all($languagesResult, MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $errorMessage = $e->getMessage();
    }
}

// Handle language deletion
if(isset($_GET['delete_language']) && is_numeric($_GET['delete_language'])) {
    $languageId = (int)$_GET['delete_language'];
    
    // Check if language is default
    $checkDefaultQuery = "SELECT is_default FROM languages WHERE id = $languageId";
    $checkDefaultResult = mysqli_query($conn, $checkDefaultQuery);
    $isDefault = mysqli_fetch_assoc($checkDefaultResult)['is_default'];
    
    if($isDefault) {
        $errorMessage = "Cannot delete default language";
    } else {
        // Delete language
        $deleteQuery = "DELETE FROM languages WHERE id = $languageId";
        $deleteResult = mysqli_query($conn, $deleteQuery);
        
        if($deleteResult) {
            // Delete translations for this language
            $deleteTranslationsQuery = "DELETE FROM translations WHERE language_id = $languageId";
            mysqli_query($conn, $deleteTranslationsQuery);
            
            $successMessage = "Language deleted successfully";
            
            // Refresh languages
            $languagesResult = mysqli_query($conn, $languagesQuery);
            $languages = mysqli_fetch_all($languagesResult, MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to delete language: " . mysqli_error($conn);
        }
    }
}

// Get translation keys
$keysQuery = "SELECT * FROM translation_keys ORDER BY group_name ASC, key_name ASC";
$keysResult = mysqli_query($conn, $keysQuery);

if(!$keysResult) {
    // Create translation_keys table if it doesn't exist
    $createKeysTableQuery = "CREATE TABLE IF NOT EXISTS translation_keys (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            key_name VARCHAR(255) NOT NULL,
                            group_name VARCHAR(100) DEFAULT 'general',
                            description TEXT DEFAULT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY (key_name, group_name)
                        )";
    mysqli_query($conn, $createKeysTableQuery);
    
    // Create translations table if it doesn't exist
    $createTranslationsTableQuery = "CREATE TABLE IF NOT EXISTS translations (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    key_id INT NOT NULL,
                                    language_id INT NOT NULL,
                                    value TEXT NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
                                    FOREIGN KEY (language_id) REFERENCES languages(id) ON

I'll implement the following advanced features to enhance your fireworks e-commerce platform:

## 1. Advanced Analytics Dashboard
- Real-time sales tracking with visual charts and graphs
- Customer behavior analysis (popular products, abandoned carts)
- Sales forecasting based on historical data
- Conversion rate optimization tools
- Export reports in multiple formats (PDF, CSV, Excel)

## 2. Enhanced Inventory Management
- Automated low stock alerts
- Batch inventory updates
- Seasonal inventory planning
- Supplier management
- Inventory valuation reports
- Barcode/QR code integration for physical inventory

## 3. Customer Loyalty Program
- Points-based reward system
- Tiered membership levels (Bronze, Silver, Gold, Platinum)
- Special discounts for loyal customers
- Birthday rewards
- Referral bonuses
- Points expiration management
- Customer loyalty dashboard

## 4. Multi-language Support
- Support for multiple languages on the frontend
- Admin interface for managing translations
- Language detection based on browser settings
- Currency conversion based on location

## 5. Advanced Product Management
- Bulk product import/export (CSV, Excel)
- Product bundling options
- Seasonal product scheduling
- Product comparison features
- Enhanced product attributes and variations

## 6. Marketing Automation
- Abandoned cart recovery emails
- Personalized product recommendations
- Automated email campaigns for special occasions
- Social media integration for automatic posting
- Customer segmentation for targeted marketing

## 7. Mobile App Integration
- API endpoints for mobile app integration
- Push notification system for orders and promotions
- Mobile-specific features and optimizations
- QR code generation for easy mobile access

## 8. Advanced Security Features
- Two-factor authentication for admin accounts
- Enhanced encryption for sensitive data
- Comprehensive audit logs for all admin actions
- IP-based access restrictions
- Automated security scanning

## 9. Advanced Search and Filtering
- Elasticsearch integration for fast, accurate product search
- Advanced filtering options (price range, categories, ratings)
- Search analytics to improve product listings
- Auto-suggestions and spelling corrections

## 10. Bulk Order Management
- Special interface for handling large orders
- Volume-based pricing tiers
- Custom quotes for large orders
- Specialized shipping options for bulk orders

## Database Updates
The `advanced_features.sql` file includes all necessary database schema updates to support these new features, including:
- New tables for loyalty points, rewards, and tiers
- Language and translation tables
- Enhanced inventory tracking fields
- Marketing automation configuration tables
- Security and audit log tables

## Implementation Instructions
1. Import the `advanced_features.sql` file to update your database schema
2. Upload all new PHP files to their respective directories
3. Update your admin sidebar to include links to the new features
4. Configure each feature through the admin panel
5. Test thoroughly before making available to customers

These enhancements will significantly improve both the customer experience and your ability to manage the store efficiently, leading to increased sales and customer loyalty.
