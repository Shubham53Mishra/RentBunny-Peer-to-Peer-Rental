-- Create deleted_users archive table to save username/user data after deletion
CREATE TABLE IF NOT EXISTS deleted_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_user_id INT NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email_address VARCHAR(255) NULL,
    full_name VARCHAR(255) NULL,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deletion_reason VARCHAR(255) NULL,
    archived_data LONGTEXT NULL COMMENT 'JSON of all user data before deletion'
);
