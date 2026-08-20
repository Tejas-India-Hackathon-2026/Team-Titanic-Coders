-- ===================================================================
-- RentNear Database Schema with Dedicated Owners and Renters Tables
-- Database Name: rentnear_db (MySQL / MariaDB / XAMPP)
-- ===================================================================

CREATE DATABASE IF NOT EXISTS `rentnear_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rentnear_db`;

-- 1. Dedicated Owners Table
CREATE TABLE IF NOT EXISTS `owners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) DEFAULT '',
    `city` VARCHAR(100) DEFAULT '',
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Dedicated Renters / Tenants Table
CREATE TABLE IF NOT EXISTS `renters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `occupation` VARCHAR(100) DEFAULT '',
    `preferred_city` VARCHAR(100) DEFAULT '',
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Dedicated Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT '',
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Properties Table (Linked to Owners)
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `property_type` VARCHAR(50) NOT NULL,
    `furnishing` VARCHAR(50) NOT NULL,
    `location` VARCHAR(200) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `deposit` DECIMAL(10, 2) NOT NULL,
    `bedrooms` INT DEFAULT 1,
    `bathrooms` INT DEFAULT 1,
    `area_sqft` INT DEFAULT 500,
    `image` VARCHAR(500) NOT NULL,
    `images_json` TEXT DEFAULT NULL,
    `amenities` TEXT DEFAULT NULL,
    `tenant_preference` VARCHAR(50) DEFAULT 'Bachelors Allowed',
    `is_premium` TINYINT(1) DEFAULT 0,
    `premium_expires_at` DATETIME DEFAULT NULL,
    `views_count` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `owners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Payments Table (Linked to Owners)
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
    `payment_type` VARCHAR(50) NOT NULL DEFAULT 'premium_listing',
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'UPI',
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `status` VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `owners`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Inquiries Table (Linked to Properties and Renters)
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `renter_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `move_in_date` DATE NULL,
    `status` VARCHAR(20) DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`renter_id`) REFERENCES `renters`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Favorites Table (Linked to Renters)
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `renter_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_favorite` (`renter_id`, `property_id`),
    FOREIGN KEY (`renter_id`) REFERENCES `renters`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
