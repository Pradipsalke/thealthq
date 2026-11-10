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
    `title` VARCHAR(255) NOT NULL,
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
-- Password hash generated with password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `name`) 
VALUES (1, 'admin', '$2y$10$h587hwExOztG05eTh41XDefD6nN9o8JHFyUp7U4hrPGUlaEq1qxj.', 'Administrator')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Seed Insights from existing index.html
INSERT INTO `insights` (`id`, `title`, `top_badge`, `category`, `headline`, `description`, `link_url`, `link_text`, `is_external`, `banner_bg`, `banner_text_color`, `badge_color`, `display_order`, `is_active`)
VALUES 
(1, 'Pune’s Next Leap', 'Leadership perspective', 'GCC Ecosystem', 'GCC leaders and government building a global innovation hub', 'Nilesh Biniwale  ·  6 min read', 'insights/punes-next-leap.html', 'Read the article →', 0, '#005B94', '#FFFFFF', '#FFD200', 1, 1),
(2, 'Beyond the execution model', 'Featured in Thoughtworks Perspectives', 'GCC Strategy', 'Global capability centers: Driving innovation and growth', 'Nilesh Kulkarni on hiring for the right mandate, building GCC leadership and measuring the value a centre delivers.', 'https://www.thoughtworks.com/perspectives/edition37-global-capability-centers/article', 'Read on thoughtworks.com ↗', 1, '#1E2229', '#FFFFFF', '#FFD200', 2, 1),
(3, 'From Triangle to Apple to Hourglass', 'Leadership · Talent · AI', 'Talent and Leadership', 'The organisation in the AI era', 'Nilesh Biniwale  ·  7 min read', 'insights/triangle-apple-hourglass.html', 'Read the article →', 0, '#FFD200', '#1E2229', '#1E2229', 3, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Seed Videos from existing index.html
INSERT INTO `videos` (`id`, `title`, `badge`, `meta_info`, `video_type`, `video_path`, `thumbnail_path`, `banner_bg`, `badge_color`, `button_text`, `display_order`, `is_active`)
VALUES 
(1, 'Building the GCC of Tomorrow: Culture, Technology and Strategy', 'Podcast · CXOTalks', 'NSquare Xperts  ·  Nilesh Biniwale', 'youtube', 'https://www.youtube.com/results?search_query=%22Building+the+GCC+of+Tomorrow%3A+Culture%2C+Technology+%26+Strategy%22', '', '#005B94', '#FFD200', 'Watch on YouTube →', 1, 1),
(2, 'Bhoom of Bharat: In Conversation with Nilesh Biniwale', 'Podcast · Bhoom of Bharat', 'SRKGameChangers  ·  Nilesh Biniwale', 'youtube', 'https://www.youtube.com/watch?v=tO_H2SfIE0o', '', '#0077B3', '#FFD200', 'Watch on YouTube →', 2, 1),
(3, 'Interview at the 8th Leadership Summit', 'Interview · IIM Raipur', 'IIM Raipur  ·  Nilesh Biniwale', 'youtube', 'https://www.youtube.com/results?search_query=%22Interview+with+Shri+Nilesh+Biniwale+%7C+IIM+Raipur%22', '', '#1E2229', '#FFD200', 'Watch on YouTube →', 3, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
