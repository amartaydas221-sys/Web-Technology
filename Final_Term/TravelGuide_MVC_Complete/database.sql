-- TravelGuide - Interactive Travel Guidance & Scout Management System
-- Fresh installation database for XAMPP / MySQL / MariaDB

DROP DATABASE IF EXISTS travelguide_db;
CREATE DATABASE travelguide_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travelguide_db;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (role_name) VALUES ('user'), ('scout'), ('admin');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(500) NULL,
    role_id INT NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE post_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scout_id INT NULL,
    title VARCHAR(180) NOT NULL,
    short_history TEXT NOT NULL,
    country VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    cost_level ENUM('Budget','Moderate','Expensive') NOT NULL DEFAULT 'Moderate',
    image_url VARCHAR(800) NULL,
    status ENUM('pending','approved','rejected','change_requested') NOT NULL DEFAULT 'pending',
    admin_feedback TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_scout (scout_id),
    INDEX idx_request_status (status),
    CONSTRAINT fk_request_scout FOREIGN KEY (scout_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_request_id INT NULL UNIQUE,
    scout_id INT NULL,
    title VARCHAR(180) NOT NULL,
    short_history TEXT NOT NULL,
    country VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    cost_level ENUM('Budget','Moderate','Expensive') NOT NULL DEFAULT 'Moderate',
    image_url VARCHAR(800) NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_post_approved (is_approved),
    INDEX idx_post_country (country),
    INDEX idx_post_category (category),
    CONSTRAINT fk_post_request FOREIGN KEY (source_request_id) REFERENCES post_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_post_scout FOREIGN KEY (scout_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comment_post (post_id),
    INDEX idx_comment_user (user_id),
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlists (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE calculator_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    destination VARCHAR(180) NOT NULL,
    travelers INT NOT NULL,
    days INT NOT NULL,
    transport DECIMAL(12,2) NOT NULL DEFAULT 0,
    accommodation DECIMAL(12,2) NOT NULL DEFAULT 0,
    food DECIMAL(12,2) NOT NULL DEFAULT 0,
    other DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_calc_user (user_id),
    CONSTRAINT fk_calc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_user (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Demo users. Passwords are listed in README.md.
-- admin@travelguide.com / Admin@123
-- scout@travelguide.com / Scout@123
-- user@travelguide.com / User@123
INSERT INTO users (name,email,password_hash,role_id,is_verified) VALUES
('Administrator','admin@travelguide.com','$2y$12$ID3A/Qb7.Z6WC18sQQ0d1ezR9KjaVpc8c6Tf7YuCl3zyM0AC7CFQi',(SELECT id FROM roles WHERE role_name='admin'),1),
('Local Scout','scout@travelguide.com','$2y$12$aK74iDxuNoViXFC1spoFU.FPIATbYnpHMTCp2fdCJFW1.pkKgeK96',(SELECT id FROM roles WHERE role_name='scout'),1),
('Registered Traveler','user@travelguide.com','$2y$12$JsAWq7DPHHcpNhnyyWvyieVFMU6ORi7wj6XhpZGk1uyIavupOJ87K',(SELECT id FROM roles WHERE role_name='user'),1);

-- Approved destination posts for demonstration.
INSERT INTO posts (scout_id,title,short_history,country,category,cost_level,image_url,is_approved) VALUES
(2,'Patagonian Valley Trek','Three hikers stand at the edge of a vast valley - the kind of silence only Patagonia can offer. This guide highlights trekking routes, weather preparation and responsible travel practices.','Argentina','Trekking','Expensive','public/assets/images/patagonia.svg',1),
(2,'Karakoram Highway','The ancient Silk Road reimagined - an arid mountain landscape split by a ribbon of road reaching toward dramatic peaks. The route rewards careful planning and respect for local conditions.','Pakistan','Road Trip','Budget','public/assets/images/karakoram.svg',1),
(2,'Cloud Forest Bridge','A suspension bridge over a rushing river, framed by forested Himalayan foothills. The location combines nature, trekking and a sense of adventure.','Nepal','Adventure','Moderate','public/assets/images/cloud-forest.svg',1),
(2,'Cappadocia Valleys','Hot-air balloons rise over carved valleys and historic cave settlements. Sunrise walks and local viewpoints make this a memorable leisure destination.','Turkey','Leisure','Moderate','public/assets/images/cappadocia.svg',1),
(2,'Himma Desert','A wide desert landscape for travelers interested in nature, photography and quiet camps under open skies.','Saudi Arabia','Nature','Expensive','public/assets/images/desert.svg',1),
(2,'Misty Green Peaks','Forest-covered hills, cool air and long walking routes create a peaceful escape for budget-conscious nature travelers.','India','Trekking','Budget','public/assets/images/green-peaks.svg',1);

-- One change-request example and one pending request so the Scout/Admin workflow is visible immediately.
INSERT INTO post_requests (scout_id,title,short_history,country,category,cost_level,status,admin_feedback) VALUES
(2,'Hidden Waterfall Trail','A remote waterfall trail with a short forest approach and several slippery sections.','Bangladesh','Nature','Budget','change_requested','Please add clearer safety and route information before resubmitting.'),
(2,'Old Town Food Walk','A walking guide focused on local food stalls, markets and cultural landmarks.','Bangladesh','Culture','Budget','pending',NULL);

INSERT INTO comments (user_id,post_id,content) VALUES
(3,1,'The details are useful. I would also compare the calculator result before planning this trek.'),
(3,4,'Cappadocia is now on my wishlist.');

INSERT INTO wishlists (user_id,post_id) VALUES (3,4),(3,3);
