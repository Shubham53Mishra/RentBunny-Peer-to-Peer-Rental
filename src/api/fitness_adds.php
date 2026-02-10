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
    
    $fitness_tables = array('massager_adds', 'cross_trainer_adds', 'excercise_bike_adds');
    $grouped_products = array();
    
    foreach($fitness_tables as $table) {
        $table_name = mysqli_real_escape_string($conn, $table);
        $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
        $table_exists = $conn->query($check_table_sql);
        
        if($table_exists && $table_exists->num_rows > 0) {
            $sql = "SELECT * FROM " . $table_name . " ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            if(!$result) {
                $response['success'] = false;
                $response['message'] = 'Database query error for ' . $table;
                $response['error'] = $conn->error;
                echo json_encode($response);
                exit;
            }
            
            $table_products = array();
            if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $table_products[] = $row;
                }
            }
            
            // Add table data to grouped products (empty array bhi add karenge)
            $grouped_products[$table] = $table_products;
        } else {
            // Table exist nahi karti to empty array
            $grouped_products[$table] = array();
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