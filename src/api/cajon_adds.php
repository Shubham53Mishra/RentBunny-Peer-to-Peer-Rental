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
    
    // Query from cajon_adds table and add product_type
    $sql = "SELECT id, user_id, title, description, price as price_per_month, `condition`, city, latitude, longitude, image_url, brand, product_type, security_deposit, created_at, updated_at FROM cajon_adds ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if($result) {
        $all_products = array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['product_type'] = 'cajon';
                $all_products[] = $row;
            }
        }
        $response['success'] = true;
        $response['count'] = count($all_products);
        $response['data'] = $all_products;
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to fetch cajons: ' . $conn->error;
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Only GET/POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
