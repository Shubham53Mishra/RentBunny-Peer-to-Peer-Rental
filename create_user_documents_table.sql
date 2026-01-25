-- Create user documents table for KYC verification
CREATE TABLE IF NOT EXISTS user_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dl_no VARCHAR(50) NULL UNIQUE,
    dl_file_path VARCHAR(255) NULL,
    dl_verified TINYINT DEFAULT 0,
    dl_uploaded_at TIMESTAMP NULL,
    pan_no VARCHAR(50) NULL UNIQUE,
    pan_file_path VARCHAR(255) NULL,
    pan_verified TINYINT DEFAULT 0,
    pan_uploaded_at TIMESTAMP NULL,
    aadhaar_no VARCHAR(50) NULL UNIQUE,
    aadhaar_file_path VARCHAR(255) NULL,
    aadhaar_verified TINYINT DEFAULT 0,
    aadhaar_uploaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_dl_verified (dl_verified),
    INDEX idx_pan_verified (pan_verified),
    INDEX idx_aadhaar_verified (aadhaar_verified),
    UNIQUE KEY unique_user (user_id)
);
