-- Create ad_reports table for ad reporting functionality
CREATE TABLE IF NOT EXISTS `ad_reports` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `add_id` INT NOT NULL,
  `report_type` VARCHAR(100) NOT NULL,
  `comment` TEXT,
  `report_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_add_id` (`add_id`),
  INDEX `idx_report_date` (`report_date`),
  CONSTRAINT `fk_ad_reports_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
