<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get token from multiple possible sources
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
    
    // Validate token
    if(empty($token)) {
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Token not provided';
        echo json_encode($response);
        exit;
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    
    // Get user from token
    $sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    
    if(!$result || $result->num_rows == 0) {
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Invalid token';
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // Fetch all ads posted by user from all tables
    $ads = array();
    
    // Define all ad tables
    $ad_tables = array(
        'furniture_adds' => 'Furniture',
        'appliance_adds' => 'Appliance',
        'bike_adds' => 'Bike',
        'camera_adds' => 'Camera',
        'car_adds' => 'Car',
        'clothing_adds' => 'Clothing',
        'cycle_adds' => 'Cycle',
        'medical_adds' => 'Medical',
        'music_adds' => 'Music',
        'kitchen_adds' => 'Kitchen',
        'fitness_adds' => 'Fitness'
    );
    
    foreach($ad_tables as $table => $category) {
        // Check if status column exists in the table
        $columns_query = "SHOW COLUMNS FROM $table LIKE 'status'";
        $columns_result = $conn->query($columns_query);
        $has_status = ($columns_result && $columns_result->num_rows > 0);
        
        // Build query based on column availability
        if($has_status) {
            $query = "SELECT id, title, description, price, status, created_at FROM $table WHERE user_id = '$user_id' ORDER BY created_at DESC";
        } else {
            $query = "SELECT id, title, description, price, 'Active' as status, created_at FROM $table WHERE user_id = '$user_id' ORDER BY created_at DESC";
        }
        
        $result = $conn->query($query);
        
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['category'] = $category;
                $row['table'] = $table;
                $ads[] = $row;
            }
        }
    }
    
    // Sort all ads by created_at descending
    usort($ads, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $response['success'] = true;
    $response['message'] = 'User ads retrieved successfully';
    $response['data'] = array(
        'total_ads' => count($ads),
        'ads' => $ads
    );
    http_response_code(200);
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use POST request.';
}

echo json_encode($response);
$conn->close();
?>
