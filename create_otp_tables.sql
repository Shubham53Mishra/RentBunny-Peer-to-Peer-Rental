-- Create email_otp table for email verification
CREATE TABLE IF NOT EXISTS email_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    is_verified TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    expires_at DATETIME GENERATED ALWAYS AS (DATE_ADD(created_at, INTERVAL 10 MINUTE)) STORED,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_email (user_id, email),
    INDEX idx_created_at (created_at)
);

-- Create mobile_otp table for mobile verification (delete flow)
CREATE TABLE IF NOT EXISTS mobile_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    is_verified TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    expires_at DATETIME GENERATED ALWAYS AS (DATE_ADD(created_at, INTERVAL 10 MINUTE)) STORED,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_mobile (user_id, mobile),
    INDEX idx_created_at (created_at)
);
