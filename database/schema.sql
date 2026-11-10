-- The ALT HQ Database Schema

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `insights` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `insight_type` ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    `slug` VARCHAR(255) DEFAULT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(500) DEFAULT NULL,
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `author_name` VARCHAR(255) DEFAULT 'Nilesh Biniwale',
    `author_role` VARCHAR(255) DEFAULT 'Managing Director, Pattern  ·  Co-author, The ALT HQ',
    `author_photo` VARCHAR(500) DEFAULT 'assets/author-biniwale.jpg',
    `author_bio` TEXT DEFAULT NULL,
    `author_linkedin` VARCHAR(500) DEFAULT 'https://www.linkedin.com/in/nileshbiniwale/',
    `read_time` VARCHAR(50) DEFAULT '6 min read',
    `published_date` DATE DEFAULT NULL,
    `top_badge` VARCHAR(255) DEFAULT '',
    `category` VARCHAR(255) DEFAULT '',
    `headline` TEXT NOT NULL,
    `description` TEXT DEFAULT NULL,
    `link_url` VARCHAR(500) NOT NULL,
    `link_text` VARCHAR(100) DEFAULT 'Read the article →',
    `is_external` TINYINT(1) DEFAULT 0,
    `banner_bg` VARCHAR(50) DEFAULT '#005B94',
    `banner_text_color` VARCHAR(50) DEFAULT '#FFFFFF',
    `badge_color` VARCHAR(50) DEFAULT '#FFD200',
    `content` LONGTEXT DEFAULT NULL,
    `seo_title` VARCHAR(255) DEFAULT NULL,
    `seo_description` TEXT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `badge` VARCHAR(255) DEFAULT '',
    `meta_info` VARCHAR(255) DEFAULT '',
    `video_type` ENUM('upload', 'youtube', 'url') DEFAULT 'youtube',
    `video_path` VARCHAR(500) NOT NULL,
    `thumbnail_path` VARCHAR(500) DEFAULT '',
    `banner_bg` VARCHAR(50) DEFAULT '#005B94',
    `badge_color` VARCHAR(50) DEFAULT '#FFD200',
    `button_text` VARCHAR(100) DEFAULT 'Watch on YouTube →',
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin User: admin / Admin@123
INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `name`) 
VALUES (1, 'admin', '$2y$10$h587hwExOztG05eTh41XDefD6nN9o8JHFyUp7U4hrPGUlaEq1qxj.', 'Administrator')
ON DUPLICATE KEY UPDATE `username` = `username`;
