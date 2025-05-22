-- Complete Fireworks E-commerce Database Schema
-- This file contains all tables needed for the complete system

-- Drop existing database and create new one
DROP DATABASE IF EXISTS fireworks_shop;
CREATE DATABASE fireworks_shop;
USE fireworks_shop;

-- Disable foreign key checks temporarily for easier table creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- CORE TABLES
-- --------------------------------------------------------

-- Admins table
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','manager','staff') NOT NULL DEFAULT 'staff',
  `permissions` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `wallet_balance` decimal(10, 2) DEFAULT 0.00,
  `loyalty_points` int(11) DEFAULT 0,
  `customer_group_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `last_birthday_points_date` date DEFAULT NULL,
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `multilingual_name` varchar(255) DEFAULT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subcategories table
CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `price` decimal(10, 2) NOT NULL,
  `sale_price` decimal(10, 2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `sku` varchar(100) DEFAULT NULL,
  `weight` decimal(10, 2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `new_arrival` tinyint(1) DEFAULT 0,
  `best_seller` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product images table
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL UNIQUE,
  `total_amount` decimal(10, 2) NOT NULL,
  `subtotal` decimal(10, 2) DEFAULT 0.00,
  `discount_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10, 2) DEFAULT 0.00,
  `shipping_amount` decimal(10, 2) DEFAULT 0.00,
  `tax_amount` decimal(10, 2) DEFAULT 0.00,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) NOT NULL,
  `shipping_pincode` varchar(20) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `billing_address` text DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `payment_transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `whatsapp_notification_sent` tinyint(1) DEFAULT 0,
  `promotion_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `discount_id` (`discount_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10, 2) NOT NULL,
  `total` decimal(10, 2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart table
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Banners table
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Testimonials table
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT 5,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site settings table
CREATE TABLE `site_settings` (
  `id` int(11) PRIMARY KEY DEFAULT 1,
  `site_name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_facebook_url` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `social_instagram_url` varchar(255) DEFAULT NULL,
  `social_twitter` varchar(255) DEFAULT NULL,
  `social_twitter_url` varchar(255) DEFAULT NULL,
  `social_youtube` varchar(255) DEFAULT NULL,
  `social_youtube_url` varchar(255) DEFAULT NULL,
  `license_text` text DEFAULT NULL,
  `b2b_enabled` tinyint(1) DEFAULT 1,
  `chat_enabled` tinyint(1) DEFAULT 1,
  `min_order_amount` decimal(10, 2) DEFAULT 2500.00,
  `shipping_fee` decimal(10, 2) DEFAULT 0.00,
  `tax_rate` decimal(5, 2) DEFAULT 18.00,
  `reviews_enabled` tinyint(1) DEFAULT 1,
  `wishlist_enabled` tinyint(1) DEFAULT 1,
  `compare_enabled` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `google_analytics_id` varchar(50) DEFAULT NULL,
  `facebook_pixel_id` varchar(50) DEFAULT NULL,
  `cod_enabled` tinyint(1) DEFAULT 1,
  `online_payment_enabled` tinyint(1) DEFAULT 1,
  `razorpay_key_id` varchar(255) DEFAULT NULL,
  `razorpay_key_secret` varchar(255) DEFAULT NULL,
  `whatsapp_notifications` tinyint(1) DEFAULT 1,
  `whatsapp_notification_number` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pages table
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `content` text NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DISCOUNT AND PROMOTION TABLES
-- --------------------------------------------------------

-- Discounts table
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10, 2) NOT NULL,
  `min_order_amount` decimal(10, 2) DEFAULT 0,
  `max_discount_amount` decimal(10, 2) DEFAULT 0,
  `usage_limit` int(11) DEFAULT 0,
  `usage_count` int(11) DEFAULT 0,
  `user_usage_limit` int(11) DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer discounts table
CREATE TABLE `customer_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL,
  `usage_count` int(11) DEFAULT 0,
  `last_used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_discount` (`user_id`, `discount_id`),
  KEY `discount_id` (`discount_id`),
  CONSTRAINT `customer_discounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_discounts_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotions table
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `minimum_purchase` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `applies_to` enum('all','categories','products') NOT NULL DEFAULT 'all',
  `banner_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotion products table
CREATE TABLE `promotion_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_product` (`promotion_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `promotion_products_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotion categories table
CREATE TABLE `promotion_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_category` (`promotion_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `promotion_categories_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- USER INTERACTION TABLES
-- --------------------------------------------------------

-- Wishlist table
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product reviews table
CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `review` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verified_purchase` tinyint(1) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `not_helpful_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Review votes table
CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `vote_type` enum('helpful','not_helpful') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `review_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product questions table
CREATE TABLE `product_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `question` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_questions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_questions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Question answers table
CREATE TABLE `question_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `is_seller` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `helpful_votes` int(11) DEFAULT 0,
  `not_helpful_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `question_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `product_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User product interactions table
CREATE TABLE `user_product_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `interaction_type` enum('view','add_to_cart','purchase','wishlist') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `user_product_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_product_interactions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- LOYALTY AND CUSTOMER MANAGEMENT TABLES
-- --------------------------------------------------------

-- Customer groups table
CREATE TABLE `customer_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `discount_percentage` decimal(5, 2) DEFAULT 0.00,
  `min_order_count` int(11) DEFAULT 0,
  `min_total_spent` decimal(10, 2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Loyalty settings table
CREATE TABLE `loyalty_settings` (
  `id` int(11) PRIMARY KEY DEFAULT 1,
  `points_per_inr` decimal(10, 2) DEFAULT 1.00,
  `points_redemption_value` decimal(10, 2) DEFAULT 0.50,
  `min_points_redemption` int(11) DEFAULT 100,
  `welcome_points` int(11) DEFAULT 50,
  `birthday_points` int(11) DEFAULT 100,
  `enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Loyalty transactions table
CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('earn', 'redeem', 'expire', 'adjust') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- INVENTORY AND PRODUCT MANAGEMENT TABLES
-- --------------------------------------------------------

-- Product attributes table
CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('text', 'number', 'select', 'radio', 'checkbox', 'color') NOT NULL DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attribute values table
CREATE TABLE `attribute_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product attribute values table
CREATE TABLE `product_attribute_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product tags table
CREATE TABLE `product_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product tag relationships table
CREATE TABLE `product_tag_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_tag` (`product_id`, `tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `product_tag_relationships_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_tag_relationships_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `product_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory logs table
CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `change_type` enum('order','manual','return','adjustment') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Low stock alerts table
CREATE TABLE `low_stock_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `threshold` int(11) NOT NULL,
  `status` enum('pending','notified','resolved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `low_stock_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import logs table
CREATE TABLE `import_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `import_type` enum('products','categories','customers') NOT NULL,
  `total_records` int(11) DEFAULT 0,
  `successful_records` int(11) DEFAULT 0,
  `failed_records` int(11) DEFAULT 0,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `error_log` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import errors table
CREATE TABLE `import_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `error_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `import_id` (`import_id`),
  CONSTRAINT `import_errors_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `import_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- RECOMMENDATION AND ANALYTICS TABLES
-- --------------------------------------------------------

-- Product recommendations table
CREATE TABLE `product_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `recommended_product_id` int(11) NOT NULL,
  `score` decimal(10,4) DEFAULT 0.0000,
  `recommendation_type` enum('similar','frequently_bought_together','also_viewed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_recommendation` (`product_id`,`recommended_product_id`,`recommendation_type`),
  KEY `recommended_product_id` (`recommended_product_id`),
  CONSTRAINT `product_recommendations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_recommendations_ibfk_2` FOREIGN KEY (`recommended_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recommendation logs table
CREATE TABLE `recommendation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `recommended_product_id` int(11) NOT NULL,
  `recommendation_type` enum('similar','frequently_bought_together','also_viewed') NOT NULL,
  `clicked` tinyint(1) DEFAULT 0,
  `converted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `recommended_product_id` (`recommended_product_id`),
  CONSTRAINT `recommendation_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recommendation_logs_ibfk_2` FOREIGN KEY (`recommended_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site visits table
CREATE TABLE `site_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_date` date NOT NULL,
  `visit_count` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity log table
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','staff','customer') NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- ABANDONED CART TABLES
-- --------------------------------------------------------

-- Abandoned carts table
CREATE TABLE `abandoned_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `recovery_status` enum('pending','email_sent','recovered','expired') NOT NULL DEFAULT 'pending',
  `recovery_token` varchar(255) DEFAULT NULL,
  `last_notification_sent` timestamp NULL DEFAULT NULL,
  `notification_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `abandoned_carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abandoned cart items table
CREATE TABLE `abandoned_cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `abandoned_cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `abandoned_carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `abandoned_cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- PAYMENT AND NOTIFICATION TABLES
-- --------------------------------------------------------

-- Payment details table
CREATE TABLE `payment_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` enum('bank','upi','other') NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WhatsApp settings table
CREATE TABLE `whatsapp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) DEFAULT NULL,
  `instance_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `admin_phone` varchar(20) DEFAULT NULL,
  `notification_template` text DEFAULT NULL,
  `status_update_template` text DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification logs table
CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` enum('order','status_update','abandoned_cart','low_stock') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `recipient` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notification_type` (`notification_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email templates table
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin notifications table
CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SEO TABLES
-- --------------------------------------------------------

-- SEO settings table
CREATE TABLE `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` enum('home','category','product','page','blog') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `schema_markup` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_reference` (`page_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO redirects table
CREATE TABLE `seo_redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_url` varchar(255) NOT NULL,
  `new_url` varchar(255) NOT NULL,
  `redirect_type` enum('301','302') NOT NULL DEFAULT '301',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `old_url` (`old_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO sitemap settings table
CREATE TABLE `seo_sitemap_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `change_frequency` enum('always','hourly','daily','weekly','monthly','yearly','never') NOT NULL DEFAULT 'weekly',
  `priority` decimal(2,1) DEFAULT 0.5,
  `include_in_sitemap` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SITE CUSTOMIZATION TABLES
-- --------------------------------------------------------

-- Theme settings table
CREATE TABLE `theme_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custom CSS table
CREATE TABLE `custom_css` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `css_code` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- ENABLE FOREIGN KEY CHECKS
-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- INSERT DEFAULT DATA
-- --------------------------------------------------------

-- Insert default admin
INSERT INTO `admins` (`name`, `email`, `password`, `role`, `active`) VALUES
('Admin', 'admin@fireworks.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1);

-- Insert default site settings
INSERT INTO `site_settings` (`id`, `site_name`, `email`, `phone`, `whatsapp`, `address`, `location`, `license_text`, `b2b_enabled`, `chat_enabled`) 
VALUES (1, 'Vamsi Crackers', 'info@vamsicrackers.com', '+91 9876543210', '+91 9876543210', '123 Main Street, City, State', 'City, State', 'Central Government Approved License Seller L.No. EXAMPLE/123/456', 1, 1);

-- Insert default categories
INSERT INTO `categories` (`name`, `multilingual_name`, `slug`, `description`, `featured`, `display_order`) VALUES 
('Kids', 'बच्चे / పిల్లలు', 'kids', 'Fireworks for kids', 1, 1),
('Family', 'परिवार / కుటుంబం', 'family', 'Fireworks for family celebrations', 1, 2),
('Teenagers', 'किशोर / టీనేజర్లు', 'teenagers', 'Fireworks for teenagers', 1, 3),
('Celebrations & Events', 'उत्सव एवं कार्यक्रम / వేడుకలు', 'celebrations-events', 'Fireworks for celebrations and events', 1, 4),
('Day Crackers', 'दिन के पटाखे / పగటి పటాసులు', 'day-crackers', 'Crackers for daytime use', 1, 5),
('Night Display', 'रात्रि प्रदर्शन / రాత్రి ప్రదర్శన', 'night-display', 'Fireworks for night display', 1, 6);

-- Insert default subcategories
INSERT INTO `subcategories` (`category_id`, `name`, `slug`, `display_order`) VALUES 
(1, 'Sparklers', 'sparklers', 1),
(1, 'Chakkars', 'chakkars', 2),
(1, 'Flower Pots', 'flower-pots', 3),
(1, 'Twinkling Star', 'twinkling-star', 4),
(2, 'Sparklers', 'family-sparklers', 1),
(2, 'Chakkars', 'family-chakkars', 2),
(2, 'Flower Pots', 'family-flower-pots', 3),
(2, 'Twinkling Star', 'family-twinkling-star', 4),
(3, 'Rockets', 'rockets', 1),
(3, 'Sparklers', 'teen-sparklers', 2),
(3, 'Chakkars', 'teen-chakkars', 3),
(3, 'Pencil', 'pencil', 4);

-- Insert default banner
INSERT INTO `banners` (`title`, `description`, `image_path`, `alt_text`, `active`) 
VALUES ('Diwali Special Offers', 'Get up to 20% off on all fireworks', 'banner1.jpg', 'Diwali Special Offers', 1);

-- Insert default pages
INSERT INTO `pages` (`title`, `slug`, `content`, `active`) VALUES
('About Us', 'about-us', '<h1>About Us</h1><p>Welcome to Vamsi Crackers, your trusted source for quality fireworks. We have been in business for over 10 years providing the best fireworks for all your celebrations.</p>', 1),
('Contact Us', 'contact-us', '<h1>Contact Us</h1><p>Get in touch with us at info@vamsicrackers.com or call us at +91 9876543210.</p>', 1),
('Shipping Policy', 'shipping-policy', '<h1>Shipping Policy</h1><p>We ship all over India. Orders above ₹2500 qualify for free shipping.</p>', 1),
('Privacy Policy', 'privacy-policy', '<h1>Privacy Policy</h1><p>We respect your privacy and are committed to protecting your personal data.</p>', 1),
('Terms and Conditions', 'terms-and-conditions', '<h1>Terms and Conditions</h1><p>By using our website, you agree to these terms and conditions.</p>', 1);

-- Insert default customer groups
INSERT INTO `customer_groups` (`name`, `discount_percentage`, `min_order_count`, `min_total_spent`, `description`) VALUES
('Bronze', 2.00, 1, 2500.00, 'Entry level loyalty tier'),
('Silver', 5.00, 3, 10000.00, 'Mid-tier loyalty customers'),
('Gold', 10.00, 5, 25000.00, 'Premium loyalty customers'),
('Platinum', 15.00, 10, 50000.00, 'VIP customers with exclusive benefits');

-- Insert default loyalty settings
INSERT INTO `loyalty_settings` (`id`, `points_per_inr`, `points_redemption_value`, `min_points_redemption`, `welcome_points`, `birthday_points`, `enabled`)
VALUES (1, 1.00, 0.50, 100, 50, 100, 1);

-- Insert default payment details
INSERT INTO `payment_details` (`payment_type`, `title`, `description`, `short_description`, `account_name`, `account_number`, `ifsc_code`, `bank_name`, `branch_name`, `display_order`, `active`) 
VALUES ('bank', 'Bank Transfer', 'Make a direct bank transfer to our account. Please use your Order ID as the payment reference. Your order will be processed once the funds have cleared in our account.', 'Direct bank transfer to our account', 'Vamsi Crackers', '1234567890', 'SBIN0012345', 'State Bank of India', 'Main Branch', 1, 1);

INSERT INTO `payment_details` (`payment_type`, `title`, `description`, `short_description`, `upi_id`, `qr_code_image`, `display_order`, `active`) 
VALUES ('upi', 'UPI Payment', 'Pay using any UPI app like Google Pay, PhonePe, Paytm, etc. Scan the QR code or use our UPI ID. Your order will be processed immediately after payment confirmation.', 'Pay using UPI apps like Google Pay, PhonePe, Paytm', 'vamsicrackers@upi', 'upi-qr.png', 2, 1);

-- Insert default email templates
INSERT INTO `email_templates` (`name`, `subject`, `body`, `variables`, `active`) VALUES
('Order Confirmation', 'Your Order #{{order_id}} has been confirmed', '<p>Dear {{customer_name}},</p><p>Thank you for your order. Your order #{{order_id}} has been confirmed and is being processed.</p><p>Order Total: ₹{{order_total}}</p><p>Regards,<br>{{site_name}} Team</p>', '["customer_name", "order_id", "order_total", "site_name"]', 1),
('Order Shipped', 'Your Order #{{order_id}} has been shipped', '<p>Dear {{customer_name}},</p><p>Your order #{{order_id}} has been shipped. You can track your order using the tracking number: {{tracking_number}}.</p><p>Regards,<br>{{site_name}} Team</p>', '["customer_name", "order_id", "tracking_number", "site_name"]', 1),
('Payment Confirmation', 'Payment Received for Order #{{order_id}}', '<p>Dear {{customer_name}},</p><p>We have received your payment for order #{{order_id}}. Your order is now being processed.</p><p>Order Total: ₹{{order_total}}</p><p>Regards,<br>{{site_name}} Team</p>', '["customer_name", "order_id", "order_total", "site_name"]', 1);

-- Insert default SEO sitemap settings
INSERT INTO `seo_sitemap_settings` (`entity_type`, `change_frequency`, `priority`, `include_in_sitemap`) VALUES
('home', 'weekly', 1.0, 1),
('products', 'daily', 0.8, 1),
('categories', 'weekly', 0.7, 1),
('pages', 'monthly', 0.5, 1);

-- Insert default theme settings
INSERT INTO `theme_settings` (`setting_key`, `setting_value`) VALUES
('primary_color', '#FF5722'),
('secondary_color', '#FFC107'),
('text_color', '#333333'),
('link_color', '#2196F3'),
('button_color', '#FF5722'),
('button_text_color', '#FFFFFF'),
('header_background', '#FFFFFF'),
('footer_background', '#212121'),
('footer_text_color', '#FFFFFF'),
('font_family', 'Roboto, sans-serif');
