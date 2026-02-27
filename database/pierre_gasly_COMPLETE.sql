-- Disable foreign key checks so tables can be dropped in any order
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- PIERRE GASLY COMPLETE DATABASE
-- Gas Delivery System - Full Structure
-- Version: 2.0 (Complete with Ratings)
-- Date: February 17, 2026
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- DATABASE CREATION
-- ============================================


-- ============================================
-- TABLE: users
-- ============================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('master_admin','sub_admin','rider','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  `valid_id` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `passkey` varchar(50) DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: categories
-- ============================================

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`category_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: brands
-- ============================================

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brand_name` (`brand_name`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_brands_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_sizes
-- ============================================

DROP TABLE IF EXISTS `product_sizes`;
CREATE TABLE `product_sizes` (
  `size_id` int(11) NOT NULL AUTO_INCREMENT,
  `size_kg` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`size_id`),
  UNIQUE KEY `size_kg` (`size_kg`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_sizes_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: products
-- ============================================

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `size_kg` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `minimum_stock` int(11) NOT NULL DEFAULT 10,
  `description` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `availability` enum('available','out_of_stock') DEFAULT 'available',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`),
  KEY `created_by` (`created_by`),
  KEY `size_kg` (`size_kg`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_products_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: orders
-- ============================================

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `payment_method` enum('cash','gcash','paymaya','card') NOT NULL DEFAULT 'cash',
  `order_status` enum('pending','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `prepared_at` timestamp NULL DEFAULT NULL,
  `out_for_delivery_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  KEY `rider_id` (`rider_id`),
  KEY `order_status` (`order_status`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_rider` FOREIGN KEY (`rider_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: sales
-- ============================================

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `sale_amount` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sale_id`),
  KEY `order_id` (`order_id`),
  KEY `rider_id` (`rider_id`),
  KEY `sale_date` (`sale_date`),
  CONSTRAINT `fk_sales_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_rider` FOREIGN KEY (`rider_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: reviews (NEW - Customer Ratings)
-- ============================================

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  KEY `rating` (`rating`),
  KEY `idx_rating_created` (`rating`,`created_at` DESC),
  KEY `idx_order_review` (`order_id`,`created_at` DESC),
  CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: review_responses (NEW - Admin Replies)
-- ============================================

DROP TABLE IF EXISTS `review_responses`;
CREATE TABLE `review_responses` (
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`response_id`),
  KEY `review_id` (`review_id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_review_responses` (`review_id`,`created_at`),
  CONSTRAINT `fk_responses_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_responses_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: activity_logs
-- ============================================

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `activity_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `activity_date` (`activity_date`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: system_settings
-- ============================================

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INITIAL DATA - Master Administrator
-- ============================================

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `birthday`, `created_at`) VALUES
(1, 'PierreGasly2025@gmail.com', '$2y$10$YourHashedPasswordHere', 'Master Administrator', '09171234567', 'master_admin', 'active', '2000-01-24', NOW());

-- Password: P1erreGaslyAdm1n_2401
-- Note: You'll need to generate the actual password hash using PHP password_hash()

-- ============================================
-- INITIAL DATA - Categories
-- ============================================

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_by`, `created_at`) VALUES
(1, 'LPG Gas Tank', 'Liquefied Petroleum Gas tanks for household and commercial use', 1, NOW()),
(2, 'Gas Refill', 'LPG gas refill service', 1, NOW());

-- ============================================
-- INITIAL DATA - Brands
-- ============================================

INSERT INTO `brands` (`brand_id`, `brand_name`, `created_by`, `created_at`) VALUES
(1, 'PETRON', 1, NOW()),
(2, 'SOLANE', 1, NOW()),
(3, 'SHELLANE', 1, NOW()),
(4, 'PRYCE GASES', 1, NOW());

-- ============================================
-- INITIAL DATA - Product Sizes
-- ============================================

INSERT INTO `product_sizes` (`size_id`, `size_kg`, `created_by`, `created_at`) VALUES
(1, 11, 1, NOW()),
(2, 22, 1, NOW()),
(3, 50, 1, NOW());

-- ============================================
-- INITIAL DATA - System Settings
-- ============================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('system_name', 'Pierre Gasly Gas Delivery', 1, NOW()),
('delivery_fee', '50.00', 1, NOW()),
('contact_email', 'support@pierregasly.com', 1, NOW()),
('contact_phone', '09171234567', 1, NOW()),
('business_hours', '8:00 AM - 6:00 PM', 1, NOW()),
('max_login_attempts', '5', 1, NOW()),
('session_timeout', '3600', 1, NOW());

-- ============================================
-- SAMPLE DATA - Products (Optional - for testing)
-- ============================================

INSERT INTO `products` (`product_id`, `category_id`, `brand_id`, `product_name`, `size_kg`, `price`, `stock_quantity`, `minimum_stock`, `description`, `availability`, `created_by`) VALUES
(1, 1, 1, 'LPG Gas Tank', 11, 850.00, 25, 10, 'PETRON LPG Gas Tank 11kg - Perfect for household use', 'available', 1),
(2, 1, 2, 'LPG Gas Tank', 22, 1200.00, 8, 10, 'SOLANE LPG Gas Tank 22kg - For bigger households', 'available', 1),
(3, 1, 3, 'LPG Gas Tank Refill', 11, 750.00, 0, 10, 'SHELLANE LPG Gas Tank 11kg Refill', 'out_of_stock', 1);

-- ============================================
-- SAMPLE DATA - Reviews (Optional - for testing)
-- ============================================

-- Note: You'll need to create customer users first, then orders, then reviews
-- This is sample structure - adjust IDs based on your actual data

/*
INSERT INTO `reviews` (`customer_id`, `order_id`, `rating`, `comment`, `created_at`) VALUES
(2, 1, 5, 'Excellent service! Fast delivery and friendly rider. The gas tank was in perfect condition.', '2026-02-15 10:30:00'),
(3, 2, 4, 'Good service overall. Delivery was a bit delayed but the product quality is great.', '2026-02-15 14:45:00'),
(4, 3, 5, 'Very satisfied! Will definitely order again. Quick response and professional service.', '2026-02-16 09:20:00'),
(5, 4, 3, 'Service was okay. The delivery took longer than expected but the product is good.', '2026-02-16 16:10:00'),
(6, 5, 5, 'Amazing experience! The rider was very courteous and the delivery was super fast. Highly recommended!', '2026-02-17 08:15:00');
*/

-- ============================================
-- SAMPLE DATA - Admin Responses (Optional - for testing)
-- ============================================

/*
INSERT INTO `review_responses` (`review_id`, `admin_id`, `response_text`, `created_at`) VALUES
(1, 1, 'Thank you for your wonderful feedback! We''re glad you had a great experience with our service. We appreciate your business!', '2026-02-15 11:00:00'),
(2, 1, 'Thank you for your review! We apologize for the slight delay and will work on improving our delivery times. We''re happy you''re satisfied with the product quality!', '2026-02-15 15:30:00'),
(4, 1, 'We appreciate your feedback! We''re sorry about the delay and will work on improving our delivery efficiency. Thank you for choosing Pierre Gasly!', '2026-02-16 17:00:00');
*/

-- ============================================
-- AUTO INCREMENT RESET
-- ============================================

ALTER TABLE `users` AUTO_INCREMENT = 2;
ALTER TABLE `categories` AUTO_INCREMENT = 3;
ALTER TABLE `brands` AUTO_INCREMENT = 5;
ALTER TABLE `product_sizes` AUTO_INCREMENT = 4;
ALTER TABLE `products` AUTO_INCREMENT = 4;
ALTER TABLE `orders` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `reviews` AUTO_INCREMENT = 1;
ALTER TABLE `review_responses` AUTO_INCREMENT = 1;
ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `system_settings` AUTO_INCREMENT = 8;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional indexes for better query performance
ALTER TABLE `products` ADD INDEX `idx_stock_check` (`stock_quantity`, `minimum_stock`);
ALTER TABLE `products` ADD INDEX `idx_brand_size` (`brand_id`, `size_kg`);
ALTER TABLE `orders` ADD INDEX `idx_customer_status` (`customer_id`, `order_status`);
ALTER TABLE `orders` ADD INDEX `idx_rider_status` (`rider_id`, `order_status`);
ALTER TABLE `sales` ADD INDEX `idx_rider_date` (`rider_id`, `sale_date`);

-- ============================================================
-- REWARDS SYSTEM TABLES
-- ============================================================

-- Rewards table: tracks each user's points + tier
CREATE TABLE IF NOT EXISTS `user_rewards` (
  `reward_id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT NOT NULL,
  `total_points`    INT NOT NULL DEFAULT 0,
  `redeemed_points` INT NOT NULL DEFAULT 0,
  `tier`            ENUM('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user` (`user_id`),
  CONSTRAINT `fk_rewards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Points transaction history
CREATE TABLE IF NOT EXISTS `reward_transactions` (
  `tx_id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL,
  `order_id`    INT DEFAULT NULL,
  `points`      INT NOT NULL,
  `type`        ENUM('earned','redeemed') NOT NULL DEFAULT 'earned',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tx_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`user_id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_tx_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rewards configuration (managed via web admin)
CREATE TABLE IF NOT EXISTS `rewards_settings` (
  `setting_id`    INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default rewards settings
INSERT IGNORE INTO `rewards_settings` (`setting_key`, `setting_value`) VALUES
('bronze_points_rate',  '100'),
('silver_points_rate',  '120'),
('gold_points_rate',    '150'),
('platinum_points_rate','200'),
('silver_threshold',    '5'),
('gold_threshold',      '15'),
('platinum_threshold',  '30'),
('redemption_rate',     '500'),
('redemption_value',    '50'),
('points_enabled',      '1');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
