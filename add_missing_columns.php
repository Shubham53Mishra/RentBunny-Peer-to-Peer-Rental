<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rent"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// List of all product tables
$tables = [
    // Vehicles
    'bike_adds', 'car_adds', 'cycle_adds',
    // Furniture
    'bed_adds', 'sofa_adds', 'dining_adds', 'sidedesk_adds',
    // Electronics
    'ac_adds', 'computer_adds', 'printer_adds', 'refrigerator_adds', 
    'tv_adds', 'washing_machine_adds', 'purifier_adds', 'chimney_adds', 
    'hob_adds', 'mixer_adds',
    // Fitness
    'treadmill_adds', 'massager_adds', 'excercise_bike_adds', 'cross_trainer_adds',
    // Clothing & Accessories
    'clothing_adds', 'dslr_adds',
    // Musical Instruments
    'harmonium_adds', 'guitar_adds', 'drum_adds', 'keyboard_adds', 
    'violin_adds', 'cajon_adds', 'tabla_adds',
    // Medical
    'crutches_adds', 'wheelchair_adds',
    // Camera
    'camera_adds'
];

$columns_to_add = ['brand', 'product_type'];
$added_count = 0;
$skipped_count = 0;

echo "<h2>Adding Missing Columns to Product Tables</h2>";
echo "<table border='1' cellpadding='10'><tr><th>Table Name</th><th>Column</th><th>Status</th></tr>";

foreach ($tables as $table) {
    foreach ($columns_to_add as $column) {
        // Check if column exists
        $check_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column'";
        
        $result = $conn->query($check_query);
        
        if ($result->num_rows > 0) {
            echo "<tr><td>$table</td><td>$column</td><td style='color: orange;'>⏭️ SKIPPED (Already exists)</td></tr>";
            $skipped_count++;
        } else {
            // Column doesn't exist, add it
            if ($column === 'brand') {
                $alter_query = "ALTER TABLE $table ADD COLUMN brand VARCHAR(100) AFTER `condition`";
            } else {
                $alter_query = "ALTER TABLE $table ADD COLUMN product_type VARCHAR(100) AFTER `condition`";
            }
            
            if ($conn->query($alter_query) === TRUE) {
                echo "<tr><td>$table</td><td>$column</td><td style='color: green;'>✅ ADDED</td></tr>";
                $added_count++;
            } else {
                echo "<tr><td>$table</td><td>$column</td><td style='color: red;'>❌ ERROR: " . $conn->error . "</td></tr>";
            }
        }
    }
}

echo "</table>";
echo "<h3 style='color: green;'>Summary: $added_count columns added, $skipped_count columns skipped</h3>";

$conn->close();
?>
