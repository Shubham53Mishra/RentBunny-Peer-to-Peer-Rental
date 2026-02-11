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
    
    // Get form data
    $add_id = isset($_POST['add_id']) ? mysqli_real_escape_string($conn, $_POST['add_id']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
    
    // Validate required fields
    if(empty($add_id)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'add_id is required';
        echo json_encode($response);
        exit;
    }
    
    if(empty($status)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'status is required';
        echo json_encode($response);
        exit;
    }
    
    // Validate status
    if(!in_array($status, array('Active', 'Closed'))) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Status must be "Active" or "Closed"';
        echo json_encode($response);
        exit;
    }
    
    // Define all ad tables
    $ad_tables = array('furniture_adds', 'appliance_adds', 'bike_adds', 'camera_adds', 'car_adds', 'clothing_adds', 'cycle_adds');
    
    $ad_found = false;
    $updated_table = '';
    
    // Check which table contains this ad and verify user ownership
    foreach($ad_tables as $table) {
        $check_sql = "SELECT id FROM $table WHERE id = '$add_id' AND user_id = '$user_id'";
        $check_result = $conn->query($check_sql);
        
        if($check_result && $check_result->num_rows > 0) {
            $ad_found = true;
            $updated_table = $table;
            break;
        }
    }
    
    if(!$ad_found) {
        http_response_code(404);
        $response['success'] = false;
        $response['message'] = 'Ad not found or you do not have permission to modify it';
        echo json_encode($response);
        exit;
    }
    
    // Update ad status
    $update_sql = "UPDATE $updated_table SET status = '$status', updated_at = NOW() WHERE id = '$add_id' AND user_id = '$user_id'";
    
    if(!$conn->query($update_sql)) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Database error: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['message'] = 'Ad status updated successfully';
    $response['data'] = array(
        'add_id' => $add_id,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
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
