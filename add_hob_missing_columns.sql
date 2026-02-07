-- Add missing columns to hob_adds table for proper data storage

ALTER TABLE hob_adds ADD COLUMN `security_deposit` DECIMAL(10, 2) DEFAULT 0 AFTER `price`;
ALTER TABLE hob_adds ADD COLUMN `model` VARCHAR(100) AFTER `brand`;
ALTER TABLE hob_adds ADD COLUMN `burner_count` VARCHAR(50) AFTER `model`;
ALTER TABLE hob_adds ADD COLUMN `product_type` VARCHAR(100) AFTER `burner_count`;

-- Verify the columns were added
SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS 
WHERE TABLE_NAME='hob_adds' AND TABLE_SCHEMA=DATABASE() 
ORDER BY ORDINAL_POSITION;
