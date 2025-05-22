-- Add new columns to site_settings table
ALTER TABLE site_settings
ADD COLUMN min_order_amount DECIMAL(10, 2) DEFAULT 2500.00,
ADD COLUMN shipping_fee DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN tax_rate DECIMAL(5, 2) DEFAULT 18.00,
ADD COLUMN reviews_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN wishlist_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN compare_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN meta_description TEXT DEFAULT NULL,
ADD COLUMN meta_keywords VARCHAR(255) DEFAULT NULL,
ADD COLUMN google_analytics_id VARCHAR(50) DEFAULT NULL,
ADD COLUMN facebook_pixel_id VARCHAR(50) DEFAULT NULL,
ADD COLUMN cod_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN online_payment_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN razorpay_key_id VARCHAR(255) DEFAULT NULL,
ADD COLUMN razorpay_key_secret VARCHAR(255) DEFAULT NULL;

-- Create discounts table
CREATE TABLE IF NOT EXISTS discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    max_discount_amount DECIMAL(10, 2) DEFAULT 0,
    usage_limit INT DEFAULT 0,
    usage_count INT DEFAULT 0,
    user_usage_limit INT DEFAULT 1,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add discount columns to orders table
ALTER TABLE orders
ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0.00 AFTER total_amount,
ADD COLUMN discount_id INT DEFAULT NULL AFTER subtotal,
ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER discount_id,
ADD COLUMN shipping_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER discount_amount,
ADD COLUMN tax_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER shipping_amount,
ADD FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL;

-- Create customer_discounts table to track usage
CREATE TABLE IF NOT EXISTS customer_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    discount_id INT NOT NULL,
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE CASCADE,
    UNIQUE KEY user_discount (user_id, discount_id)
);

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY user_product (user_id, product_id)
);

-- Create product_reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    review TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create staff table for admin users with different roles
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    permissions TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create activity_log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_type ENUM('admin', 'staff', 'customer') NOT NULL,
    action VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create inventory_log table
CREATE TABLE IF NOT EXISTS inventory_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    admin_id INT DEFAULT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    adjustment INT NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- Create customer_groups table
CREATE TABLE IF NOT EXISTS customer_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add customer_group_id to users table
ALTER TABLE users
ADD COLUMN customer_group_id INT DEFAULT NULL,
ADD FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id) ON DELETE SET NULL;

-- Create email_templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample discount
INSERT INTO discounts (name, code, description, discount_type, discount_value, min_order_amount, start_date, end_date, active)
VALUES ('Welcome Discount', 'WELCOME10', 'Get 10% off on your first order', 'percentage', 10.00, 1000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1);

-- Insert sample customer group
INSERT INTO customer_groups (name, discount_percentage, description)
VALUES ('VIP Customers', 5.00, 'Loyal customers who get 5% discount on all orders');

-- Insert sample email templates
INSERT INTO email_templates (name, subject, body, variables)
VALUES 
('Order Confirmation', 'Your Order #{{order_id}} has been confirmed', '<p>Dear {{customer_name}},</p><p>Thank you for your order. Your order #{{order_id}} has been confirmed and is being processed.</p><p>Order Total: ₹{{order_total}}</p><p>Regards,<br>{{site_name}} Team</p>', '["customer_name", "order_id", "order_total", "site_name"]'),
('Order Shipped', 'Your Order #{{order_id}} has been shipped', '<p>Dear {{customer_name}},</p><p>Your order #{{order_id}} has been shipped. You can track your order using the tracking number: {{tracking_number}}.</p><p>Regards,<br>{{site_name}} Team</p>', '["customer_name", "order_id", "tracking_number", "site_name"]');
