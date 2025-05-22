-- Create database
CREATE DATABASE IF NOT EXISTS fireworks_shop;
USE fireworks_shop;

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    pincode VARCHAR(20) DEFAULT NULL,
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    multilingual_name VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    display_order INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create subcategories table
CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    display_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100) DEFAULT NULL,
    featured TINYINT(1) DEFAULT 0,
    new_arrival TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_pincode VARCHAR(20) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    status VARCHAR(50) DEFAULT 'pending',
    tracking_number VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create banners table
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    display_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) DEFAULT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    rating INT DEFAULT 5,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create site_settings table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY DEFAULT 1,
    site_name VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    favicon VARCHAR(255) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    social_facebook VARCHAR(255) DEFAULT NULL,
    social_facebook_url VARCHAR(255) DEFAULT NULL,
    social_instagram VARCHAR(255) DEFAULT NULL,
    social_instagram_url VARCHAR(255) DEFAULT NULL,
    social_twitter VARCHAR(255) DEFAULT NULL,
    social_twitter_url VARCHAR(255) DEFAULT NULL,
    social_youtube VARCHAR(255) DEFAULT NULL,
    social_youtube_url VARCHAR(255) DEFAULT NULL,
    license_text TEXT DEFAULT NULL,
    b2b_enabled TINYINT(1) DEFAULT 1,
    chat_enabled TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admins (name, email, password) VALUES ('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert default site settings
INSERT INTO site_settings (id, site_name, email, phone, whatsapp, address, location, license_text, b2b_enabled, chat_enabled) 
VALUES (1, 'Fireworks Shop', 'info@fireworksshop.com', '+91 9876543210', '+91 9876543210', '123 Main Street, City, State', 'City, State', 'Central Government Approved License Seller L.No. EXAMPLE/123/456', 1, 1);

-- Insert sample categories
INSERT INTO categories (name, multilingual_name, description, featured, display_order) VALUES 
('Kids', 'बच्चे / పిల్లలు', 'Fireworks for kids', 1, 1),
('Family', 'परिवार / కుటుంబం', 'Fireworks for family celebrations', 1, 2),
('Teenagers', 'किशोर / టీనేజర్లు', 'Fireworks for teenagers', 1, 3),
('Celebrations & Events', 'उत्सव एवं कार्यक्रम / వేడుకలు', 'Fireworks for celebrations and events', 1, 4),
('Day Crackers', 'दिन के पटाखे / పగటి పటాసులు', 'Crackers for daytime use', 1, 5),
('Night Display', 'रात्रि प्रदर्शन / రాత్రి ప్రదర్శన', 'Fireworks for night display', 1, 6);

-- Insert sample subcategories
INSERT INTO subcategories (category_id, name, display_order) VALUES 
(1, 'Sparklers', 1),
(1, 'Chakkars', 2),
(1, 'Flower Pots', 3),
(1, 'Twinkling Star', 4),
(2, 'Sparklers', 1),
(2, 'Chakkars', 2),
(2, 'Flower Pots', 3),
(2, 'Twinkling Star', 4),
(3, 'Rockets', 1),
(3, 'Sparklers', 2),
(3, 'Chakkars', 3),
(3, 'Pencil', 4),
(4, 'Sparklers', 1),
(4, 'Chakkars', 2),
(4, 'Flower Pots', 3),
(4, 'Twinkling Star', 4),
(5, 'Sparklers', 1),
(5, 'Chakkars', 2),
(5, 'Flower Pots', 3),
(5, 'Twinkling Star', 4),
(6, 'Sparklers', 1),
(6, 'Chakkars', 2),
(6, 'Flower Pots', 3),
(6, 'Twinkling Star', 4);

-- Insert sample banner
INSERT INTO banners (title, description, image_path, alt_text, active) 
VALUES ('Diwali Special Offers', 'Get up to 20% off on all fireworks', 'banner1.jpg', 'Diwali Special Offers', 1);
