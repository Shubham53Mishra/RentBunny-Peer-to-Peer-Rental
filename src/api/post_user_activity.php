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
    
    // Get POST data
    $add_id = isset($_POST['add_id']) ? intval($_POST['add_id']) : 0;
    $whatsapp = isset($_POST['whatsapp']) ? intval($_POST['whatsapp']) : 0;
    $called = isset($_POST['called']) ? intval($_POST['called']) : 0;
    $contacted_to = isset($_POST['contacted_to']) ? mysqli_real_escape_string($conn, $_POST['contacted_to']) : '';
    
    // Validate required fields
    if(empty($add_id) || empty($contacted_to)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'add_id and contacted_to are required';
        echo json_encode($response);
        exit;
    }
    
    // Validate whatsapp and called are 0 or 1
    $whatsapp = ($whatsapp == 1) ? 1 : 0;
    $called = ($called == 1) ? 1 : 0;
    
    // At least one action should be true
    if($whatsapp == 0 && $called == 0) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Either whatsapp or called must be 1';
        echo json_encode($response);
        exit;
    }
    
    // Insert user activity
    $insert_sql = "INSERT INTO user_activity (user_id, add_id, contacted_to, whatsapp, called) 
                   VALUES ('$user_id', '$add_id', '$contacted_to', '$whatsapp', '$called')";
    
    if($conn->query($insert_sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Activity tracked successfully';
        $response['data'] = array(
            'activity_id' => $conn->insert_id,
            'whatsapp_clicked' => (bool)$whatsapp,
            'call_clicked' => (bool)$called,
            'contacted_to' => $contacted_to
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
$conn->close();
?>
