-- =============================================================================
-- DATABASE INITIALIZATION
-- =============================================================================
CREATE DATABASE IF NOT EXISTS `travelguide_db`
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;
USE `travelguide_db`;
-- -----------------------------------------------------------------------------
-- 1. Users Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin', 'scout') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- -----------------------------------------------------------------------------
-- 2. Destinations Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `country` VARCHAR(100) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `budget_type` ENUM('Budget', 'Moderate', 'Expensive') DEFAULT 'Moderate',
    `description` TEXT,
    `image_color` VARCHAR(30) DEFAULT 'green',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- -----------------------------------------------------------------------------
-- 3. Calculator History Log
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `calculator_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_email` VARCHAR(150),
    `destination` VARCHAR(150),
    `travelers` INT,
    `days` INT,
    `transport` DECIMAL(10,2),
    `accommodation` DECIMAL(10,2),
    `food` DECIMAL(10,2),
    `other` DECIMAL(10,2),
    `total` DECIMAL(10,2),
    `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- -----------------------------------------------------------------------------
-- Sample Seed Data
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin User',  'admin@travelguide.com',  '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234', 'admin'),
('Scout User',  'scout@travelguide.com',  '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234', 'scout'),
('Normal User', 'user@travelguide.com',   '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234', 'user');
INSERT INTO `destinations` (`name`, `country`, `category`, `budget_type`, `description`, `image_color`) VALUES
('Patagonian Valley Trek', 'Argentina', 'Trekking',  'Expensive', 'Three hikers stand at the edge of a vast valley — the kind of silence only Patagonia can offer.', 'green'),
('Karakoram Highway',      'Pakistan',  'Road Trip', 'Budget',    'The ancient Silk Road reimagined — an arid mountain landscape split by a ribbon of tarmac reaching the clouds.', 'orange'),
('Cloud Forest Bridge',    'Nepal',     'Adventure', 'Moderate',  'A suspension bridge over a rushing river, framed by forested Himalayan foothills — adventure''s perfect threshold.', 'blue'),
('Santorini Sunset Walk',  'Greece',    'Leisure',   'Expensive', 'Walk along white-washed steps as the Aegean sun dips below the horizon, painting the sky gold and pink.', 'pink'),
('Sahara Desert Camp',     'Morocco',   'Adventure', 'Moderate',  'Sleep under a canopy of a million stars in the world''s largest hot desert. Camels optional.', 'orange'),
('Bali Rice Terraces',     'Indonesia', 'Nature',    'Budget',    'Wind through emerald-green terraced fields that have fed Balinese families for centuries.', 'green');