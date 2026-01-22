-- Create Bike Tables
CREATE TABLE IF NOT EXISTS petrol_bike_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    variant VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    kilometer_driven INT NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS electric_bike_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    variant VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    kilometer_driven INT NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    battery_capacity VARCHAR(50),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS petrol_bike_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES petrol_bike_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

CREATE TABLE IF NOT EXISTS electric_bike_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES electric_bike_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

-- Create Car Tables
CREATE TABLE IF NOT EXISTS petrol_car_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    variant VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    kilometer_driven INT NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS electric_car_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    variant VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    kilometer_driven INT NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    battery_capacity VARCHAR(50),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS petrol_car_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES petrol_car_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

CREATE TABLE IF NOT EXISTS electric_car_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES electric_car_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

-- Create Furniture Tables
CREATE TABLE IF NOT EXISTS bed_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    frame_material VARCHAR(100) NOT NULL,
    storage_included TINYINT(1) DEFAULT 0,
    product_type VARCHAR(100) NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS bed_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES bed_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

CREATE TABLE IF NOT EXISTS sofa_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    frame_material VARCHAR(100) NOT NULL,
    seating_capacity INT NOT NULL,
    seat_foam_type VARCHAR(100) NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS sofa_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES sofa_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);

CREATE TABLE IF NOT EXISTS dining_table_adds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    height DECIMAL(8, 2) NOT NULL,
    length DECIMAL(8, 2) NOT NULL,
    width DECIMAL(8, 2) NOT NULL,
    primary_material VARCHAR(100) NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    ad_title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_city (city),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS dining_table_adds_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    add_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (add_id) REFERENCES dining_table_adds(id) ON DELETE CASCADE,
    INDEX idx_add_id (add_id)
);
