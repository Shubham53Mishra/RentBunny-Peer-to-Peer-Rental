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
    
    $bike_tables = array('petrol_bike_adds', 'electric_bike_adds');
    $data = array();
    
    foreach($bike_tables as $table) {
        $table_name = mysqli_real_escape_string($conn, $table);
        $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
        $table_exists = $conn->query($check_table_sql);
        
        if($table_exists && $table_exists->num_rows > 0) {
            $sql = "SELECT * FROM " . $table_name . " ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0) {
                $products = array();
                while($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
                $data[$table] = $products;
            } else {
                $data[$table] = array();
            }
        } else {
            $data[$table] = array();
        }
    }
    
    $response['success'] = true;
    $response['data'] = $data;
} else {
    $response['success'] = false;
    $response['message'] = 'Only GET/POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
