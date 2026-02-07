-- Add brand and product_type columns to all product tables
-- For MySQL 5.7+ (without IF NOT EXISTS support for columns)
-- Run only the statements for tables that don't have the columns yet

-- STEP 1: RUN THIS FIRST TO CHECK WHICH COLUMNS EXIST
-- Comment out the ALTER statements below for tables that already have brand/product_type
-- Uncomment only the ones your tables need

-- Vehicles
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='bike_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE bike_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE bike_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='car_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE car_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE car_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='cycle_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE cycle_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE cycle_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Furniture
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='bed_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE bed_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE bed_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='sofa_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE sofa_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE sofa_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='dining_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE dining_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE dining_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='sidedesk_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE sidedesk_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE sidedesk_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Electronics - Home Appliances
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='ac_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE ac_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE ac_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='computer_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE computer_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE computer_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='printer_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE printer_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE printer_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='refrigerator_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE refrigerator_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE refrigerator_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='tv_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE tv_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE tv_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='washing_machine_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE washing_machine_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE washing_machine_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='purifier_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE purifier_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE purifier_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='chimney_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE chimney_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE chimney_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='hob_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE hob_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE hob_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='mixer_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE mixer_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE mixer_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Fitness
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='treadmill_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE treadmill_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE treadmill_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='massager_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE massager_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE massager_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='excercise_bike_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE excercise_bike_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE excercise_bike_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='cross_trainer_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE cross_trainer_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE cross_trainer_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Clothing & Accessories
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='clothing_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE clothing_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE clothing_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='dslr_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE dslr_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE dslr_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Musical Instruments
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='harmonium_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE harmonium_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE harmonium_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='guitar_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE guitar_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE guitar_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='drum_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE drum_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE drum_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='keyboard_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE keyboard_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE keyboard_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='violin_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE violin_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE violin_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='cajon_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE cajon_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE cajon_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='tabla_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE tabla_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE tabla_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Medical Equipment
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='crutches_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE crutches_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE crutches_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='wheelchair_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE wheelchair_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE wheelchair_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;

-- Camera/DSLR
-- Check: SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='camera_adds' AND COLUMN_NAME IN ('brand','product_type');
ALTER TABLE camera_adds ADD COLUMN brand VARCHAR(100) AFTER `condition`;
ALTER TABLE camera_adds ADD COLUMN product_type VARCHAR(100) AFTER `condition`;
