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
    $report_type = isset($_POST['report_type']) ? mysqli_real_escape_string($conn, $_POST['report_type']) : '';
    $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, $_POST['comment']) : '';
    
    // Validate required fields
    if(empty($add_id) || empty($report_type)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'add_id and report_type are required';
        echo json_encode($response);
        exit;
    }
    
    // Validate report_type
    $valid_report_types = array('Offensive content', 'Fraud', 'Duplicate Ad', 'Product Already sold', 'Other');
    if(!in_array($report_type, $valid_report_types)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Invalid report_type. Must be one of: Offensive content, Fraud, Duplicate Ad, Product Already sold, Other';
        echo json_encode($response);
        exit;
    }
    
    // Check if user already reported this ad
    $check_sql = "SELECT id FROM ad_reports WHERE user_id = '$user_id' AND add_id = '$add_id' AND status = 'pending'";
    $check_result = $conn->query($check_sql);
    
    if($check_result && $check_result->num_rows > 0) {
        http_response_code(409);
        $response['success'] = false;
        $response['message'] = 'You have already reported this ad';
        echo json_encode($response);
        exit;
    }
    
    // Insert report
    $insert_sql = "INSERT INTO ad_reports (user_id, add_id, report_type, comment) 
                   VALUES ('$user_id', '$add_id', '$report_type', '$comment')";
    
    if($conn->query($insert_sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Ad reported successfully';
        $response['data'] = array(
            'report_id' => $conn->insert_id,
            'add_id' => $add_id,
            'report_type' => $report_type,
            'status' => 'pending'
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
