-- Add user profile columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_address VARCHAR(255) NULL UNIQUE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_verified TINYINT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT DEFAULT 0;

-- Add indexes for better query performance
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email_address (email_address);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_phone (phone);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email_verified (email_verified);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_phone_verified (phone_verified);
