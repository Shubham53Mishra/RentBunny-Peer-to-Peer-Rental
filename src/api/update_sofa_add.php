<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

// Get token from header
$token = '';
if(function_exists('getallheaders')) {
    $headers = getallheaders();
    $token = isset($headers['token']) ? $headers['token'] : (isset($headers['Token']) ? $headers['Token'] : '');
}
if(empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
}
if(empty($token) && isset($_SERVER['HTTP_TOKEN'])) {
    $token = $_SERVER['HTTP_TOKEN'];
}
if(empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
}

if(empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token not provided']);
    exit;
}

$token = mysqli_real_escape_string($conn, $token);
$sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
$result = $conn->query($sql);
if($result->num_rows == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user_row = $result->fetch_assoc();
$user_id = $user_row['id'];

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Required fields for sofa ad update
$required_fields = ['frame_material', 'seating_capacity', 'seat_foam_type', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'add_id'];

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

foreach($required_fields as $field) {
    if(!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize input
$add_id = intval($input['add_id']);
$frame_material = mysqli_real_escape_string($conn, $input['frame_material']);
$seating_capacity = intval($input['seating_capacity']);
$seat_foam_type = mysqli_real_escape_string($conn, $input['seat_foam_type']);
$product_type = mysqli_real_escape_string($conn, $input['product_type']);
$price_per_month = floatval($input['price_per_month']);
$security_deposit = floatval($input['security_deposit']);
$ad_title = mysqli_real_escape_string($conn, $input['ad_title']);
$description = mysqli_real_escape_string($conn, $input['description']);

$table_name = 'sofa_adds';

// Verify ownership
$verify_sql = "SELECT id FROM $table_name WHERE id = '$add_id' AND user_id = '$user_id'";
$verify_result = $conn->query($verify_sql);
if($verify_result->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ad']);
    exit;
}

// Update database
$update_sql = "UPDATE $table_name SET frame_material = '$frame_material', seating_capacity = '$seating_capacity', 
               seat_foam_type = '$seat_foam_type', product_type = '$product_type', price_per_month = '$price_per_month', 
               security_deposit = '$security_deposit', ad_title = '$ad_title', description = '$description', updated_at = NOW() 
               WHERE id = '$add_id' AND user_id = '$user_id'";

if($conn->query($update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Sofa ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update sofa ad: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
