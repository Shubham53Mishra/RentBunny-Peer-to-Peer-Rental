-- Create user_blocks table for blocking functionality
CREATE TABLE IF NOT EXISTS `user_blocks` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `blocked_user_id` INT NOT NULL,
  `block_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_block` (`user_id`, `blocked_user_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_blocked_user_id` (`blocked_user_id`),
  CONSTRAINT `fk_user_blocks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_blocks_blocked_user_id` FOREIGN KEY (`blocked_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
