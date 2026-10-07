-- Create Database
CREATE DATABASE IF NOT EXISTS `foodplan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodplan`;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO `categories` (`slug`, `name`) VALUES
('all', 'All Dishes'),
('bowls', 'Salads & Bowls'),
('burgers', 'Gourmet Burgers'),
('pizza', 'Artisan Pizza'),
('pasta', 'Handmade Pasta'),
('drinks', 'Drinks & Smoothies');

-- 2. Dishes / Products Table
CREATE TABLE IF NOT EXISTS `dishes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_slug` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `rating` DECIMAL(2, 1) DEFAULT 4.8,
    `badge` VARCHAR(50) DEFAULT 'Popular',
    `image_url` TEXT NOT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_slug`) REFERENCES `categories`(`slug`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Initial Seed Dishes
INSERT INTO `dishes` (`category_slug`, `name`, `description`, `price`, `rating`, `badge`, `image_url`) VALUES
('bowls', 'Garden Fresh Avocado Bowl', 'Made with whole farm produce, cold-pressed dressings & fresh herbs.', 14.50, 4.9, "Chef's Choice", 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80'),
('burgers', 'Truffle Mushroom Burger', 'Artisan brioche bun, portobello mushroom, and black truffle aioli.', 16.00, 4.8, 'Bestseller', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=500&q=80'),
('pizza', 'Woodfired Margherita Pizza', 'San Marzano tomatoes, buffalo mozzarella, and fresh basil.', 15.20, 4.7, 'Popular', 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=500&q=80'),
('pasta', 'Pesto Basil Penne Pasta', 'Al dente penne folded in freshly pounded pine nut and herb pesto.', 13.80, 4.8, 'Organic', 'https://images.unsplash.com/photo-1621996346565-e3d5d6281724?auto=format&fit=crop&w=500&q=80'),
('bowls', 'Crispy Salmon Quinoa Salad', 'Wild Norwegian salmon, tri-color quinoa, baby spinach, citrus vinaigrette.', 18.00, 4.9, 'Healthy', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=500&q=80'),
('drinks', 'Berry Acai Smoothie Bowl', 'Organic frozen acai puree with chia seeds, banana, and granola.', 9.50, 4.6, 'Fresh', 'https://images.unsplash.com/photo-1590301157890-4810ed352733?auto=format&fit=crop&w=500&q=80'),
('burgers', 'Smoked Wagyu Smash Burger', 'Grass-fed double beef patty, cheddar, caramelized onions, house relish.', 17.50, 5.0, 'Trending', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?auto=format&fit=crop&w=500&q=80'),
('drinks', 'Matcha Mint Iced Tea', 'Ceremonial grade Uji matcha shaken with spearmint and raw agave.', 6.00, 4.8, 'Cold Brew', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=500&q=80');

-- 3. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_code` VARCHAR(20) NOT NULL UNIQUE,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(30) NOT NULL,
    `delivery_address` TEXT NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'Cash on Delivery',
    `subtotal` DECIMAL(10, 2) NOT NULL,
    `delivery_fee` DECIMAL(10, 2) DEFAULT 2.50,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `order_status` ENUM('pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `dish_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dish_id`) REFERENCES `dishes`(`id`)
) ENGINE=InnoDB;

-- 5. Table Reservations Table
CREATE TABLE IF NOT EXISTS `reservations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `res_date` DATE NOT NULL,
    `guests` VARCHAR(50) NOT NULL,
    `special_requests` TEXT,
    `status` ENUM('confirmed', 'pending', 'cancelled') DEFAULT 'confirmed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;