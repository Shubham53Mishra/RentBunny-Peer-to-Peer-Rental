-- Add security_deposit column to appliance tables
ALTER TABLE ac_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;
ALTER TABLE refrigerator_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;
ALTER TABLE washing_machine_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;
ALTER TABLE purifier_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;
ALTER TABLE tv_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;
ALTER TABLE printer_adds ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0;

-- Verify columns were added
SHOW COLUMNS FROM ac_adds;
SHOW COLUMNS FROM refrigerator_adds;
SHOW COLUMNS FROM washing_machine_adds;
SHOW COLUMNS FROM purifier_adds;
SHOW COLUMNS FROM tv_adds;
SHOW COLUMNS FROM printer_adds;
