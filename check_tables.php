<?php
header('Content-Type: application/json');
require_once('config/database.php');

$conn = new mysqli('localhost', 'root', '', 'rentbunny');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check which tables exist
$tables_needed = array(
    'petrol_bike_adds' => array('brand', 'variant', 'year', 'price_per_month', 'security_deposit', 'kilometer_driven', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'electric_bike_adds' => array('brand', 'variant', 'year', 'price_per_month', 'security_deposit', 'kilometer_driven', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'petrol_car_adds' => array('brand', 'variant', 'year', 'price_per_month', 'security_deposit', 'kilometer_driven', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'electric_car_adds' => array('brand', 'variant', 'year', 'price_per_month', 'security_deposit', 'kilometer_driven', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'bed_adds' => array('frame_material', 'storage_included', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'sofa_adds' => array('frame_material', 'seating_capacity', 'seat_foam_type', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'dining_table_adds' => array('height', 'length', 'width', 'primary_material', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'),
    'petrol_bike_adds_images' => array('add_id', 'image_path'),
    'electric_bike_adds_images' => array('add_id', 'image_path'),
    'petrol_car_adds_images' => array('add_id', 'image_path'),
    'electric_car_adds_images' => array('add_id', 'image_path'),
    'bed_adds_images' => array('add_id', 'image_path'),
    'sofa_adds_images' => array('add_id', 'image_path'),
    'dining_table_adds_images' => array('add_id', 'image_path')
);

$status = array();

foreach($tables_needed as $table_name => $required_columns) {
    // Check if table exists
    $check_table = "SELECT 1 FROM information_schema.tables WHERE table_schema = 'rentbunny' AND table_name = '$table_name'";
    $table_result = $conn->query($check_table);
    
    if($table_result && $table_result->num_rows > 0) {
        // Table exists, check columns
        $check_columns = "SHOW COLUMNS FROM $table_name";
        $columns_result = $conn->query($check_columns);
        $existing_columns = array();
        
        while($row = $columns_result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($required_columns, $existing_columns);
        
        $status[$table_name] = array(
            'exists' => true,
            'columns_count' => count($existing_columns),
            'existing_columns' => $existing_columns,
            'required_columns' => $required_columns,
            'missing_columns' => $missing_columns,
            'status' => empty($missing_columns) ? 'OK' : 'MISSING COLUMNS'
        );
    } else {
        // Table doesn't exist
        $status[$table_name] = array(
            'exists' => false,
            'required_columns' => $required_columns,
            'status' => 'TABLE NOT FOUND'
        );
    }
}

// Summary
$problems = array();
$all_ok = true;

foreach($status as $table => $info) {
    if(!$info['exists']) {
        $problems[] = "❌ $table - TABLE NOT CREATED";
        $all_ok = false;
    } elseif(!empty($info['missing_columns'])) {
        $problems[] = "❌ $table - MISSING COLUMNS: " . implode(', ', $info['missing_columns']);
        $all_ok = false;
    }
}

$response = array(
    'overall_status' => $all_ok ? '✅ ALL TABLES OK' : '❌ PROBLEMS FOUND',
    'total_tables_checked' => count($tables_needed),
    'tables_status' => $status,
    'problems' => $problems
);

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
