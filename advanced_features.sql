-- Create loyalty_settings table
CREATE TABLE IF NOT EXISTS loyalty_settings (
    id INT PRIMARY KEY DEFAULT 1,
    points_per_inr DECIMAL(10, 2) DEFAULT 1.00,
    points_redemption_value DECIMAL(10, 2) DEFAULT 0.50,
    min_points_redemption INT DEFAULT 100,
    welcome_points INT DEFAULT 50,
    birthday_points INT DEFAULT 100,
    enabled TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add loyalty points fields to users table
ALTER TABLE users
ADD COLUMN loyalty_points INT DEFAULT 0,
ADD COLUMN date_of_birth DATE DEFAULT NULL,
ADD COLUMN last_birthday_points_date DATE DEFAULT NULL;

-- Add fields to customer_groups table
ALTER TABLE customer_groups
ADD COLUMN min_order_count INT DEFAULT 0,
ADD COLUMN min_total_spent DECIMAL(10, 2) DEFAULT 0.00;

-- Create site_visits table for tracking conversion rates
CREATE TABLE IF NOT EXISTS site_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_date DATE NOT NULL,
    visit_count INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (visit_date)
);

-- Create loyalty_transactions table
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    transaction_type ENUM('earn', 'redeem', 'expire', 'adjust') NOT NULL,
    reference_id INT DEFAULT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create pages table if not exists
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

-- Insert default loyalty settings
INSERT INTO loyalty_settings (id, points_per_inr, points_redemption_value, min_points_redemption, welcome_points, birthday_points, enabled)
VALUES (1, 1.00, 0.50, 100, 50, 100, 1)
ON DUPLICATE KEY UPDATE id = 1;

-- Insert sample customer groups
INSERT INTO customer_groups (name, discount_percentage, min_order_count, min_total_spent, description)
VALUES 
('Bronze', 2.00, 1, 2500.00, 'Entry level loyalty tier'),
('Silver', 5.00, 3, 10000.00, 'Mid-tier loyalty customers'),
('Gold', 10.00, 5, 25000.00, 'Premium loyalty customers'),
('Platinum', 15.00, 10, 50000.00, 'VIP customers with exclusive benefits');

-- Insert sample site visits data
INSERT INTO site_visits (visit_date, visit_count, unique_visitors)
VALUES 
(DATE_SUB(CURDATE(), INTERVAL 30 DAY), 120, 85),
(DATE_SUB(CURDATE(), INTERVAL 29 DAY), 135, 92),
(DATE_SUB(CURDATE(), INTERVAL 28 DAY), 142, 98),
(DATE_SUB(CURDATE(), INTERVAL 27 DAY), 128, 87),
(DATE_SUB(CURDATE(), INTERVAL 26 DAY), 115, 79),
(DATE_SUB(CURDATE(), INTERVAL 25 DAY), 98, 68),
(DATE_SUB(CURDATE(), INTERVAL 24 DAY), 105, 72),
(DATE_SUB(CURDATE(), INTERVAL 23 DAY), 132, 91),
(DATE_SUB(CURDATE(), INTERVAL 22 DAY), 145, 102),
(DATE_SUB(CURDATE(), INTERVAL 21 DAY), 138, 95),
(DATE_SUB(CURDATE(), INTERVAL 20 DAY), 125, 88),
(DATE_SUB(CURDATE(), INTERVAL 19 DAY), 118, 82),
(DATE_SUB(CURDATE(), INTERVAL 18 DAY), 130, 91),
(DATE_SUB(CURDATE(), INTERVAL 17 DAY), 142, 99),
(DATE_SUB(CURDATE(), INTERVAL 16 DAY), 156, 108),
(DATE_SUB(CURDATE(), INTERVAL 15 DAY), 148, 103),
(DATE_SUB(CURDATE(), INTERVAL 14 DAY), 135, 94),
(DATE_SUB(CURDATE(), INTERVAL 13 DAY), 128, 89),
(DATE_SUB(CURDATE(), INTERVAL 12 DAY), 140, 97),
(DATE_SUB(CURDATE(), INTERVAL 11 DAY), 152, 105),
(DATE_SUB(CURDATE(), INTERVAL 10 DAY), 165, 115),
(DATE_SUB(CURDATE(), INTERVAL 9 DAY), 158, 110),
(DATE_SUB(CURDATE(), INTERVAL 8 DAY), 145, 101),
(DATE_SUB(CURDATE(), INTERVAL 7 DAY), 138, 96),
(DATE_SUB(CURDATE(), INTERVAL 6 DAY), 150, 104),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 162, 113),
(DATE_SUB(CURDATE(), INTERVAL 4 DAY), 175, 122),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 168, 117),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 155, 108),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 148, 103);
