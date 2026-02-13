<?php
/**
 * Database Setup Script - Creates Payment Tables
 * Run this script to initialize payment tables
 */

require_once '../config/database.php';

echo "Starting database setup...\n\n";

// SQL statements to create tables
$queries = array(
    "payments" => "
        CREATE TABLE IF NOT EXISTS payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            order_id VARCHAR(100) UNIQUE NOT NULL,
            payment_id VARCHAR(100),
            amount INT NOT NULL COMMENT 'Amount in paise',
            currency VARCHAR(10) DEFAULT 'INR',
            status VARCHAR(50) DEFAULT 'created' COMMENT 'created, authorized, failed, completed, pending, cancelled',
            description VARCHAR(500),
            booking_id INT,
            product_id INT,
            rental_period INT COMMENT 'in days',
            payment_method VARCHAR(50),
            razorpay_signature VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_payment_id (payment_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "rentals" => "
        CREATE TABLE IF NOT EXISTS rentals (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, confirmed, active, completed, cancelled',
            total_cost INT NOT NULL COMMENT 'in paise',
            payment_id INT,
            deposit_amount INT COMMENT 'in paise',
            deposit_status VARCHAR(50) DEFAULT 'not_collected',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_payment_id (payment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
);

// Execute queries
foreach($queries as $table_name => $query) {
    if($conn->query($query) === TRUE) {
        echo "✓ Table '{$table_name}' created/verified successfully\n";
    } else {
        echo "✗ Error creating table '{$table_name}': " . $conn->error . "\n";
    }
}

echo "\n✓ Database setup completed!\n";

// Create logs directory
$logs_dir = '../src/api/payments/logs';
if(!is_dir($logs_dir)) {
    if(mkdir($logs_dir, 0755, true)) {
        echo "✓ Logs directory created\n";
    }
}

echo "\nNext steps:\n";
echo "1. Update config/razorpay.php with your Razorpay secret key\n";
echo "2. Configure webhook in Razorpay dashboard\n";
echo "3. Test payment at: http://localhost/Rent/public/payment.html\n";

?>
