<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('config/database.php');

if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// List of tables where we need to add security_deposit column
$tables = ['ac_adds', 'refrigerator_adds', 'washing_machine_adds', 'purifier_adds', 'tv_adds', 'printer_adds'];

foreach($tables as $table) {
    // Check if column already exists
    $check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = 'security_deposit'";
    $result = $conn->query($check_sql);
    
    if($result->num_rows == 0) {
        // Column doesn't exist, add it
        $alter_sql = "ALTER TABLE $table ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0";
        if($conn->query($alter_sql)) {
            echo "✓ Added security_deposit column to $table\n";
        } else {
            echo "✗ Error adding security_deposit to $table: " . $conn->error . "\n";
        }
    } else {
        echo "✓ security_deposit column already exists in $table\n";
    }
}

echo "\n=== All tables updated ===\n";
$conn->close();
?>
