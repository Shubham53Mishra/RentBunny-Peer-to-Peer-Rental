<?php
ob_start();
header('Content-Type: application/json');
require_once('../common/db.php');
ob_end_clean();

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
    
    // Get POST data
    $block_user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($conn, $_POST['user_id']) : '';
    
    // Validate required field
    if(empty($block_user_id)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'user_id is required';
        echo json_encode($response);
        exit;
    }
    
    // Cannot block yourself
    if($user_id == $block_user_id) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Cannot block yourself';
        echo json_encode($response);
        exit;
    }
    
    // Check if already blocked
    $check_sql = "SELECT id FROM blocked_users WHERE user_id = '$user_id' AND blocked_user_id = '$block_user_id'";
    $check_result = $conn->query($check_sql);
    
    if($check_result && $check_result->num_rows > 0) {
        http_response_code(409);
        $response['success'] = false;
        $response['message'] = 'User is already blocked';
        echo json_encode($response);
        exit;
    }
    
    // Insert block
    $insert_sql = "INSERT INTO blocked_users (user_id, blocked_user_id) 
                   VALUES ('$user_id', '$block_user_id')";
    
    if($conn->query($insert_sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'User blocked successfully';
        $response['data'] = array(
            'user_id' => $user_id,
            'blocked_user_id' => $block_user_id,
            'blocked_at' => date('Y-m-d H:i:s')
        );
        http_response_code(201);
    } else {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Database error: ' . $conn->error;
    }
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use POST request.';
}

echo json_encode($response);
if($conn) {
    $conn->close();
}
?>
