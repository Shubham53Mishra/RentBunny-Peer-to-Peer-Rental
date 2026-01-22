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

// Required fields - only add_id
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['add_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Missing required field: add_id"]);
    exit;
}

// Sanitize input - optional fields
$add_id = intval($input['add_id']);
$brand = isset($input['brand']) ? mysqli_real_escape_string($conn, $input['brand']) : null;
$capacity = isset($input['capacity']) ? mysqli_real_escape_string($conn, $input['capacity']) : null;
$product_type = isset($input['product_type']) ? mysqli_real_escape_string($conn, $input['product_type']) : null;
$price_per_month = isset($input['price_per_month']) ? floatval($input['price_per_month']) : null;
$description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : null;

// Map to existing table columns
$title = null;
if($product_type && $brand && $capacity) {
    $title = "$product_type - $brand ($capacity)";
}
$price = $price_per_month;
$condition = 'good';

$table_name = 'ac_adds';

// Verify ownership
$verify_sql = "SELECT id FROM $table_name WHERE id = '$add_id' AND user_id = '$user_id'";
$verify_result = $conn->query($verify_sql);
if($verify_result->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ad']);
    exit;
}

// Update database - dynamic query
$update_fields = [];
if($title) $update_fields[] = "title = '$title'";
if($description) $update_fields[] = "description = '$description'";
if($price) $update_fields[] = "price = '$price'";

$update_fields[] = "updated_at = NOW()";
$update_sql = "UPDATE $table_name SET " . implode(', ', $update_fields) . " WHERE id = '$add_id' AND user_id = '$user_id'";

if($conn->query($update_sql)) {
    http_response_code(200);
    $response['success'] = true;
    $response['message'] = 'AC ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update AC ad: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
