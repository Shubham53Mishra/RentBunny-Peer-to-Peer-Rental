<?php
header('Content-Type: application/json');
require_once('../common/db.php');



$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get token from multiple sources
    $token = '';
    
    // Check headers using getallheaders()
    if(function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = isset($headers['token']) ? $headers['token'] : (isset($headers['Token']) ? $headers['Token'] : '');
    }
    
    // Alternative: Check Authorization header
    if(empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    
    // Alternative: Check custom header directly
    if(empty($token) && isset($_SERVER['HTTP_TOKEN'])) {
        $token = $_SERVER['HTTP_TOKEN'];
    }
    
    if(empty($token)) {
        $response['success'] = false;
        $response['message'] = 'Token not provided. Please add token in header.';
        echo json_encode($response);
        exit;
    }
    
    // Validate token and get user
    $token = mysqli_real_escape_string($conn, $token);
    $sql = "SELECT id, mobile FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    
    if($result->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'Invalid token or user not authenticated';
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // Define all categories
    $categories = array(
        'bike_adds',
        'car_adds',
        'bed_adds',
        'sofa_adds',
        'dining_adds',
        'sidetable_adds',
        'treadmill_adds',
        'massager_adds',
        'excercise_bike_adds',
        'cross_trainer_adds',
        'ac_adds',
        'computer_adds',
        'printer_adds',
        'refrigerator_adds',
        'tv_adds',
        'washing_machine_adds',
        'purifier_adds',
        'chimney_adds',
        'hob_adds',
        'mixer_adds',
        'clothing_adds',
        'dslr_adds',
        'harmonium_adds',
        'guitar_adds',
        'drum_adds',
        'keyboard_adds',
        'violin_adds',
        'cajon_adds',
        'tabla_adds',
        'crutches_adds',
        'wheelchair_adds',
        'cycle_adds'
    );
    
    $data = array();
    
    // Fetch products for each category
    foreach($categories as $category) {
        $table_name = mysqli_real_escape_string($conn, $category);
        
        // Check if table exists first
        $check_table_sql = "SELECT 1 FROM information_schema.tables 
                           WHERE table_schema = DATABASE() 
                           AND table_name = '$table_name' LIMIT 1";
        $table_exists = $conn->query($check_table_sql);
        
        if($table_exists && $table_exists->num_rows > 0) {
            $sql = "SELECT * FROM " . $table_name . " WHERE user_id = " . intval($user_id) . " ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            if($result && $result->num_rows > 0) {
                $products = array();
                while($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
                $data[$category] = $products;
            } else {
                $data[$category] = array();
            }
        } else {
            // Table doesn't exist yet
            $data[$category] = array();
        }
    }
    
    $response['success'] = true;
    $response['data'] = $data;
    
} else {
    $response['success'] = false;
    $response['message'] = 'Only POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
