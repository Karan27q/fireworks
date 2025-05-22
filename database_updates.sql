-- Create pages table for managing static pages
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add WhatsApp notification settings to site_settings
ALTER TABLE site_settings
ADD COLUMN whatsapp_notifications TINYINT(1) DEFAULT 1,
ADD COLUMN whatsapp_notification_number VARCHAR(20) DEFAULT NULL;

-- Create activity_log table if not exists
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_type ENUM('admin', 'staff', 'customer') NOT NULL,
    action VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin if not exists
INSERT INTO admins (name, email, password) 
SELECT 'Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email = 'admin@example.com');

-- Add new columns to site_settings table
ALTER TABLE site_settings
ADD COLUMN primary_color VARCHAR(20) DEFAULT '#4caf50',
ADD COLUMN secondary_color VARCHAR(20) DEFAULT '#ff6b00',
ADD COLUMN footer_text TEXT DEFAULT NULL,
ADD COLUMN homepage_show_featured_categories TINYINT(1) DEFAULT 1,
ADD COLUMN homepage_featured_categories_title VARCHAR(255) DEFAULT 'Featured Categories',
ADD COLUMN homepage_show_featured_products TINYINT(1) DEFAULT 1,
ADD COLUMN homepage_featured_products_title VARCHAR(255) DEFAULT 'Featured Products',
ADD COLUMN homepage_show_new_arrivals TINYINT(1) DEFAULT 0,
ADD COLUMN homepage_new_arrivals_title VARCHAR(255) DEFAULT 'New Arrivals',
ADD COLUMN homepage_show_testimonials TINYINT(1) DEFAULT 0,
ADD COLUMN homepage_testimonials_title VARCHAR(255) DEFAULT 'Customer Testimonials',
ADD COLUMN header_show_top_bar TINYINT(1) DEFAULT 1,
ADD COLUMN header_top_bar_text VARCHAR(255) DEFAULT 'Central Government Approved License Seller',
ADD COLUMN header_show_secondary_nav TINYINT(1) DEFAULT 1,
ADD COLUMN header_show_social_icons TINYINT(1) DEFAULT 1,
ADD COLUMN footer_columns INT DEFAULT 4,
ADD COLUMN footer_copyright VARCHAR(255) DEFAULT NULL,
ADD COLUMN product_products_per_page INT DEFAULT 12,
ADD COLUMN product_layout VARCHAR(20) DEFAULT 'grid',
ADD COLUMN product_show_rating TINYINT(1) DEFAULT 1,
ADD COLUMN product_show_stock TINYINT(1) DEFAULT 1,
ADD COLUMN product_show_related TINYINT(1) DEFAULT 1,
ADD COLUMN product_related_title VARCHAR(255) DEFAULT 'Related Products';
