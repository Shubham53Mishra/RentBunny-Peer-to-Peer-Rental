-- Add missing columns to cycle_adds table if they don't exist

ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS model VARCHAR(100) AFTER brand;
ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS product_type VARCHAR(100) AFTER model;
ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS security_deposit DECIMAL(10,2) DEFAULT 0 AFTER product_type;

-- Verify all required columns exist with correct data types
-- user_id: INT - required
-- title: VARCHAR - required
-- description: TEXT - optional
-- price: DECIMAL - optional (stores price_per_month)
-- city: VARCHAR - required
-- latitude: DECIMAL - required
-- longitude: DECIMAL - required
-- image_url: VARCHAR or JSON (for multiple URLs)
-- brand: VARCHAR - required
-- model: VARCHAR - required
-- product_type: VARCHAR - required
-- security_deposit: DECIMAL - required
-- created_at: TIMESTAMP - required
-- updated_at: TIMESTAMP - required
