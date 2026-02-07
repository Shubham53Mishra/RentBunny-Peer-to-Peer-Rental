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
    
    $kitchen_tables = array('chimney_adds', 'hob_adds', 'mixer_adds');
    $all_products = array();
    
    foreach($kitchen_tables as $table) {
        $table_name = mysqli_real_escape_string($conn, $table);
        $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
        $table_exists = $conn->query($check_table_sql);
        
        if($table_exists && $table_exists->num_rows > 0) {
            $sql = "SELECT * FROM " . $table_name . " ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Transform row to match standardized format
                    $product = [
                        'id' => $row['id'],
                        'user_id' => $row['user_id'],
                        'price_per_month' => isset($row['price']) ? $row['price'] : 0,
                        'security_deposit' => isset($row['security_deposit']) ? $row['security_deposit'] : 0,
                        'ad_title' => isset($row['title']) ? $row['title'] : '',
                        'description' => isset($row['description']) ? $row['description'] : '',
                        'city' => isset($row['city']) ? $row['city'] : '',
                        'latitude' => isset($row['latitude']) ? $row['latitude'] : 0,
                        'longitude' => isset($row['longitude']) ? $row['longitude'] : 0,
                        'brand' => isset($row['brand']) ? $row['brand'] : '',
                        'product_type' => isset($row['product_type']) && !empty($row['product_type']) ? $row['product_type'] : str_replace('_adds', '', $table)
                    ];
                    
                    // Parse image_url JSON to image_urls array
                    if(isset($row['image_url'])) {
                        $product['image_urls'] = json_decode($row['image_url'], true) ?: [];
                    } else {
                        $product['image_urls'] = [];
                    }
                    
                    // Include timestamps if available
                    if(isset($row['created_at'])) {
                        $product['created_at'] = $row['created_at'];
                    }
                    if(isset($row['updated_at'])) {
                        $product['updated_at'] = $row['updated_at'];
                    }
                    
                    $all_products[] = $product;
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
