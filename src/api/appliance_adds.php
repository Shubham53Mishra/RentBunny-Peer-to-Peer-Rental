<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    
    $appliance_tables = array('ac_adds', 'refrigerator_adds', 'washing_machine_adds', 'purifier_adds', 'tv_adds', 'computer_adds', 'printer_adds');
    $all_products = array();
    
    foreach($appliance_tables as $table) {
        $table_name = mysqli_real_escape_string($conn, $table);
        $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
        $table_exists = $conn->query($check_table_sql);
        
        if($table_exists && $table_exists->num_rows > 0) {
            // Select only necessary fields - use COALESCE for optional fields
            $sql = "SELECT id, user_id, title, description, price, city, latitude, longitude, image_url, brand, product_type, COALESCE(security_deposit, 0) as security_deposit, created_at, updated_at FROM " . $table_name . " ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if(!$result) {
                $response['success'] = false;
                $response['message'] = 'Database query error for ' . $table;
                $response['error'] = $conn->error;
                echo json_encode($response);
                exit;
            }
            if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Parse image_url JSON array to image_urls
                    $image_urls = array();
                    if(!empty($row['image_url'])) {
                        $decoded = json_decode($row['image_url'], true);
                        $image_urls = is_array($decoded) ? $decoded : [$row['image_url']];
                    }
                    
                    // Format response with expected field names
                    $formatted_row = [
                        'id' => $row['id'],
                        'user_id' => $row['user_id'],
                        'ad_title' => $row['title'],
                        'description' => $row['description'],
                        'price_per_month' => floatval($row['price']),
                        'city' => $row['city'],
                        'latitude' => floatval($row['latitude']),
                        'longitude' => floatval($row['longitude']),
                        'image_urls' => $image_urls,
                        'brand' => $row['brand'],
                        'product_type' => $row['product_type'],
                        'security_deposit' => floatval($row['security_deposit']),
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at']
                    ];
                    $all_products[] = $formatted_row;
                }
            }
        }
    }
    
    // Sort all products by created_at in descending order
    usort($all_products, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $response['success'] = true;
    $response['count'] = count($all_products);
    $response['data'] = $all_products;
} else {
    $response['success'] = false;
    $response['message'] = 'Only GET/POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
