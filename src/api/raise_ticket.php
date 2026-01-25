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
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    
    // Validate required field
    if(empty($description)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'description is required';
        echo json_encode($response);
        exit;
    }
    
    // Validate description length (min 10, max 1000 characters)
    if(strlen($description) < 10) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Description must be at least 10 characters long';
        echo json_encode($response);
        exit;
    }
    
    if(strlen($description) > 1000) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Description must not exceed 1000 characters';
        echo json_encode($response);
        exit;
    }
    
    // Insert support ticket record
    $insert_sql = "INSERT INTO support_tickets (user_id, description, status, created_at) 
                   VALUES ('$user_id', '$description', 'open', NOW())";
    
    if(!$conn->query($insert_sql)) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Database error: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    $ticket_id = $conn->insert_id;
    
    $response['success'] = true;
    $response['message'] = 'Support ticket raised successfully';
    $response['data'] = array(
        'ticket_id' => $ticket_id,
        'description' => $description,
        'status' => 'open',
        'created_at' => date('Y-m-d H:i:s')
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
