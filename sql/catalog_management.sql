-- Create categories table if not exists
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT,
    image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create product attributes table if not exists
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('text', 'number', 'select', 'radio', 'checkbox', 'color') NOT NULL DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create attribute values table if not exists
CREATE TABLE IF NOT EXISTS attribute_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attribute_id INT NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE
);

-- Create product attribute relationships table if not exists
CREATE TABLE IF NOT EXISTS product_attribute_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE
);

-- Create product tags table if not exists
CREATE TABLE IF NOT EXISTS product_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create product tag relationships table if not exists
CREATE TABLE IF NOT EXISTS product_tag_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES product_tags(id) ON DELETE CASCADE
);

-- Add category_id column to products table if not exists
ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT;
ALTER TABLE products ADD CONSTRAINT IF NOT EXISTS fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Insert some sample categories
INSERT INTO categories (name, description, parent_id, display_order, is_featured) VALUES
('Sparklers', 'Various types of sparklers for all occasions', NULL, 1, 1),
('Rockets', 'Exciting rocket fireworks that shoot into the sky', NULL, 2, 1),
('Ground Spinners', 'Fireworks that spin on the ground creating beautiful patterns', NULL, 3, 0),
('Aerial Fireworks', 'Fireworks that explode in the air with colorful displays', NULL, 4, 1),
('Fountains', 'Stationary fireworks that emit sparks and colorful flames', NULL, 5, 0),
('Novelty Fireworks', 'Fun and unique fireworks for special occasions', NULL, 6, 0),
('Hand Sparklers', 'Sparklers that can be held safely in hand', 1, 1, 0),
('Color Sparklers', 'Sparklers that emit colorful sparks', 1, 2, 0),
('Small Rockets', 'Smaller rockets for backyard displays', 2, 1, 0),
('Large Rockets', 'Larger rockets for bigger celebrations', 2, 2, 0),
('Diwali Special', 'Special fireworks for Diwali celebrations', NULL, 0, 1);

-- Insert some sample product attributes
INSERT INTO product_attributes (name, type) VALUES
('Color', 'select'),
('Duration', 'number'),
('Height', 'number'),
('Noise Level', 'select'),
('Safety Rating', 'select'),
('Age Recommendation', 'select');

-- Insert sample attribute values
INSERT INTO attribute_values (attribute_id, value) VALUES
(1, 'Red'),
(1, 'Green'),
(1, 'Blue'),
(1, 'Gold'),
(1, 'Silver'),
(1, 'Multicolor'),
(4, 'Low'),
(4, 'Medium'),
(4, 'High'),
(5, 'Safe for Children'),
(5, 'Adult Supervision Required'),
(5, 'Adults Only'),
(6, '5+'),
(6, '10+'),
(6, '12+'),
(6, '18+');

-- Insert sample product tags
INSERT INTO product_tags (name) VALUES
('Diwali'),
('New Year'),
('Wedding'),
('Birthday'),
('Festival'),
('Eco-Friendly'),
('Smokeless'),
('Colorful'),
('Loud'),
('Silent'),
('Best Seller'),
('New Arrival'),
('Limited Edition');
