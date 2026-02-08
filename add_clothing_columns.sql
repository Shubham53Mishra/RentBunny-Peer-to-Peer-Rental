-- Add missing columns to clothing_adds table for proper product information storage

-- Check and add price_per_month column if it doesn't exist
ALTER TABLE clothing_adds 
ADD COLUMN IF NOT EXISTS price_per_month DECIMAL(10, 2) AFTER price;

-- Check and add product_type column if it doesn't exist
ALTER TABLE clothing_adds 
ADD COLUMN IF NOT EXISTS product_type VARCHAR(255) AFTER brand;

-- Check and add security_deposit column if it doesn't exist
ALTER TABLE clothing_adds 
ADD COLUMN IF NOT EXISTS security_deposit DECIMAL(10, 2) AFTER price_per_month;

-- Optional: Remove condition column if it exists (as per requirement)
-- ALTER TABLE clothing_adds DROP COLUMN IF EXISTS `condition`;

-- Display table structure after migration
DESCRIBE clothing_adds;
