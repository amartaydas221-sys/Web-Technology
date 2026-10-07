USE `foodplan`;

CREATE TABLE IF NOT EXISTS ff_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'rider') NOT NULL DEFAULT 'customer',
    active TINYINT(1) NOT NULL DEFAULT 1,
    rider_status ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'offline',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    image_url VARCHAR(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    store_name VARCHAR(120) NOT NULL DEFAULT 'Foodplan Kitchen',
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    image_url VARCHAR(500) NOT NULL DEFAULT '',
    price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
    stock INT UNSIGNED NOT NULL DEFAULT 50,
    available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES ff_categories(id),
    INDEX idx_ff_products_available (available, category_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(30) NOT NULL DEFAULT 'Home',
    address VARCHAR(500) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    instructions VARCHAR(500) NOT NULL DEFAULT '',
    FOREIGN KEY (user_id) REFERENCES ff_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL DEFAULT '',
    discount_type ENUM('percent', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(24) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    rider_id BIGINT UNSIGNED DEFAULT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    delivery_address VARCHAR(500) NOT NULL,
    delivery_instructions VARCHAR(500) NOT NULL DEFAULT '',
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 50,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(40) DEFAULT NULL,
    delivery_otp CHAR(6) NOT NULL,
    payment_method ENUM('cod', 'bkash', 'nagad', 'card', 'mobile_banking') NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    status ENUM('placed', 'confirmed', 'processing', 'packaging', 'ready', 'out_for_delivery', 'picked_up', 'on_the_way', 'arrived', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES ff_users(id),
    FOREIGN KEY (rider_id) REFERENCES ff_users(id),
    INDEX idx_ff_orders_user (user_id, created_at),
    INDEX idx_ff_orders_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_rider_locations (
    rider_id BIGINT UNSIGNED PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rider_id) REFERENCES ff_users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_rider_order_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    rider_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ff_rider_order_request (order_id, rider_id),
    INDEX idx_ff_rider_requests_status (status, created_at),
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES ff_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ff_products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES ff_users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ff_products(id),
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE SET NULL,
    CONSTRAINT chk_ff_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED DEFAULT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    status ENUM('open', 'in_progress', 'resolved') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES ff_users(id),
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(500) NOT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES ff_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    reservation_date DATE NOT NULL,
    guests SMALLINT UNSIGNED NOT NULL,
    notes VARCHAR(500) NOT NULL DEFAULT '',
    status ENUM('requested', 'confirmed', 'cancelled') NOT NULL DEFAULT 'requested',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ff_reservation_date (reservation_date, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ff_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    method VARCHAR(30) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO ff_categories (slug, name) VALUES
('biriyani', 'Biriyani'), ('burgers', 'Burgers'), ('pizza', 'Pizza'),
('chicken', 'Chicken'), ('desserts', 'Desserts'), ('drinks', 'Drinks'),
('traditional', 'Traditional Food'), ('fast-food', 'Fast Food');

INSERT INTO ff_products (category_id, name, description, ingredients, image_url, price, discount_percent, rating, stock)
SELECT c.id, seed.name, seed.description, seed.ingredients, seed.image_url, seed.price, seed.discount_percent, seed.rating, seed.stock
FROM (
    SELECT 'biriyani' AS slug, 'Dhaka kacchi biriyani' AS name, 'Slow-cooked mutton kacchi with fragrant basmati rice, potato, and warming spices.' AS description, 'Basmati rice, mutton, potato, yogurt, saffron, traditional spice blend' AS ingredients, 'https://images.unsplash.com/photo-1563379091339-03246963d51a?auto=format&fit=crop&w=900&q=85' AS image_url, 320 AS price, 10 AS discount_percent, 4.9 AS rating, 30 AS stock
    UNION ALL SELECT 'burgers', 'Smoky chicken burger', 'Grilled chicken, crisp lettuce, house sauce, and a soft toasted bun.', 'Chicken, lettuce, tomato, house sauce, brioche bun', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=85', 240, 0, 4.7, 40
    UNION ALL SELECT 'traditional', 'Beef bhuna khichuri', 'Comforting rice and lentils served with deeply spiced slow-cooked beef.', 'Beef, rice, lentils, ginger, garlic, Bengali spices', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=85', 280, 0, 4.8, 24
    UNION ALL SELECT 'chicken', 'Crispy fried chicken', 'Golden, crunchy chicken with a juicy center and a side of seasoned fries.', 'Chicken, buttermilk, flour, house seasoning, potatoes', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=900&q=85', 210, 5, 4.6, 35
    UNION ALL SELECT 'pizza', 'Garden paneer pizza', 'A crisp, oven-baked pizza topped with paneer, peppers, and fresh herbs.', 'Paneer, mozzarella, bell peppers, tomato, wheat dough', 'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?auto=format&fit=crop&w=900&q=85', 450, 0, 4.7, 18
    UNION ALL SELECT 'traditional', 'Chicken tehari', 'Aromatic one-pot rice cooked with tender chicken and classic Dhaka spices.', 'Chicken, fragrant rice, yogurt, green chilli, spices', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=85', 230, 0, 4.6, 28
    UNION ALL SELECT 'desserts', 'Mango mishti doi', 'Chilled, creamy sweet yogurt with ripe mango and a delicate caramel note.', 'Milk, yogurt culture, mango, date-palm sugar', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=85', 120, 0, 4.8, 16
    UNION ALL SELECT 'drinks', 'Fresh mint lemonade', 'Fresh lemon and garden mint shaken together over ice.', 'Lemon, mint, cane sugar, sparkling water', 'https://images.unsplash.com/photo-1513558161293-cdaf765edfd7?auto=format&fit=crop&w=900&q=85', 90, 0, 4.5, 50
) AS seed
INNER JOIN ff_categories c ON c.slug = seed.slug
WHERE NOT EXISTS (SELECT 1 FROM ff_products p WHERE p.name = seed.name);

INSERT IGNORE INTO ff_coupons (code, description, discount_type, discount_value, minimum_order, active)
VALUES ('WELCOME25', 'A warm welcome: 25% off your first order.', 'percent', 25, 300, 1),
       ('FOODFLOW50', '৳50 off orders over ৳500.', 'fixed', 50, 500, 1);
