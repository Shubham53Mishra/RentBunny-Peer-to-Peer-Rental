-- Create user activity table to track interactions (call/whatsapp)
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    add_id INT NOT NULL,
    contacted_to VARCHAR(15) NOT NULL,
    whatsapp TINYINT DEFAULT 0,
    called TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_add_id (add_id),
    INDEX idx_contacted_to (contacted_to),
    INDEX idx_created_at (created_at)
);

-- Create ad reports table
CREATE TABLE IF NOT EXISTS ad_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    add_id INT NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    comment TEXT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_add_id (add_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Create blocked users table
CREATE TABLE IF NOT EXISTS blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blocked_user_mobile VARCHAR(15) NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_blocked_user_mobile (blocked_user_mobile),
    UNIQUE KEY unique_block (user_id, blocked_user_mobile)
);
