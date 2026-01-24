-- Create mobile_otp table for mobile verification (used in delete account flow)
CREATE TABLE IF NOT EXISTS mobile_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mobile VARCHAR(10) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_mobile (mobile),
    INDEX idx_created_at (created_at)
);
