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
    
    // Query from the treadmill_adds table
    $sql = "SELECT * FROM treadmill_adds ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $all_products = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Transform row to match POST response format
            $product = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'ad_title' => $row['title'], // Map 'title' to 'ad_title'
                'description' => $row['description'],
                'price_per_month' => $row['price'], // Map 'price' to 'price_per_month'
                'city' => $row['city'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'image_url' => $row['image_url'],
                'brand' => $row['brand'],
                'product_type' => $row['product_type'],
                'security_deposit' => $row['security_deposit'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
            $all_products[] = $product;
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
