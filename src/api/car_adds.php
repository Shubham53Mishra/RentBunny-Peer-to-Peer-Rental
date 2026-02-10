<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    if(empty($token) && function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = isset($headers['token']) ? $headers['token'] : (isset($headers['Token']) ? $headers['Token'] : '');
    }
    if(empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    if(empty($token) && isset($_SERVER['HTTP_TOKEN'])) {
        $token = $_SERVER['HTTP_TOKEN'];
    }
    if(empty($token)) {
        $response['success'] = false;
        $response['message'] = 'Token not provided';
        echo json_encode($response);
        exit;
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    $sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    if($result->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'Invalid token';
        echo json_encode($response);
        exit;
    }
    
    // Check if car_adds table exists
    $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'car_adds' LIMIT 1";
    $table_exists = $conn->query($check_table_sql);
    
    $grouped_products = array(
        'petrol_car_adds' => array(),
        'diesel_car_adds' => array(),
        'electric_car_adds' => array(),
        'other_car_adds' => array()
    );
    
    if($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT * FROM car_adds ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Determine category based on fuel_type or product_type
                $category = 'other_car_adds'; // Default category
                
                // Check fuel_type field first
                if(!empty($row['fuel_type'])) {
                    $fuel = ucfirst(strtolower(trim($row['fuel_type'])));
                    if($fuel == 'Petrol') {
                        $category = 'petrol_car_adds';
                    } elseif($fuel == 'Diesel') {
                        $category = 'diesel_car_adds';
                    } elseif($fuel == 'Electric') {
                        $category = 'electric_car_adds';
                    }
                } elseif(!empty($row['product_type'])) {
                    // Fallback to product_type
                    $type = ucfirst(strtolower(trim($row['product_type'])));
                    if($type == 'Petrol') {
                        $category = 'petrol_car_adds';
                    } elseif($type == 'Diesel') {
                        $category = 'diesel_car_adds';
                    } elseif($type == 'Electric') {
                        $category = 'electric_car_adds';
                    }
                } else {
                    // Parse from title if both are empty
                    $title = strtolower($row['title']);
                    if(stripos($title, 'petrol') !== false) {
                        $category = 'petrol_car_adds';
                    } elseif(stripos($title, 'diesel') !== false) {
                        $category = 'diesel_car_adds';
                    } elseif(stripos($title, 'electric') !== false || stripos($title, 'ev') !== false) {
                        $category = 'electric_car_adds';
                    }
                }
                
                $grouped_products[$category][] = $row;
            }
        }
    }
    
    $response['success'] = true;
    $response['data'] = $grouped_products;
} else {
    $response['success'] = false;
    $response['message'] = 'Only GET/POST method allowed';
}

echo json_encode($response);
$conn->close();
?>