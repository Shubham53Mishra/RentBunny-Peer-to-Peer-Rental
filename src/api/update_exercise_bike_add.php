<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../common/db.php');

$response = array();

// Debug: Check database connection
if(!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'error' => mysqli_connect_error()]);
    exit;
}

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
if(!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed', 'error' => $conn->error]);
    exit;
}
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

// Check if JSON parsing failed
if($input === null && file_get_contents('php://input') !== '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON format', 'error' => json_last_error_msg()]);
    exit;
}

if(!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No JSON data received', 'received_data' => gettype($input)]);
    exit;
}

if(!isset($input['add_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Missing required field: add_id"]);
    exit;
}

// Sanitize input - optional fields
$add_id = intval($input['add_id']);
$brand = isset($input['brand']) ? mysqli_real_escape_string($conn, $input['brand']) : null;
$product_type = isset($input['product_type']) ? mysqli_real_escape_string($conn, $input['product_type']) : null;
$price_per_month = isset($input['price_per_month']) ? floatval($input['price_per_month']) : null;
$security_deposit = isset($input['security_deposit']) ? floatval($input['security_deposit']) : null;
$max_user_weight = isset($input['max_user_weight']) ? floatval($input['max_user_weight']) : null;
$description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : null;
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
$city = isset($input['city']) ? mysqli_real_escape_string($conn, $input['city']) : null;

// Handle image URLs validation
$image_urls = null;
if(isset($input['image_urls']) && is_array($input['image_urls'])) {
    $validated_urls = array();
    foreach($input['image_urls'] as $url) {
        if(filter_var($url, FILTER_VALIDATE_URL)) {
            $validated_urls[] = mysqli_real_escape_string($conn, $url);
        }
    }
    $image_urls = !empty($validated_urls) ? json_encode($validated_urls) : null;
}

// Map to existing table columns
$title = null;
if($product_type && $brand) {
    $title = "$product_type - $brand";
}
$price = $price_per_month;
$condition = 'good';

$table_name = 'excercise_bike_adds';

// Verify ownership
$verify_sql = "SELECT id FROM $table_name WHERE id = '$add_id' AND user_id = '$user_id'";
$verify_result = $conn->query($verify_sql);
if($verify_result->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ad']);
    exit;
}

// Validate numeric values
if($price_per_month !== null && $price_per_month <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
    exit;
}
if($max_user_weight !== null && $max_user_weight <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'max_user_weight must be greater than 0']);
    exit;
}
if($latitude !== null && ($latitude < -90 || $latitude > 90)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid latitude value']);
    exit;
}
if($longitude !== null && ($longitude < -180 || $longitude > 180)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid longitude value']);
    exit;
}

// Update database - dynamic query
$update_fields = [];
if($title) $update_fields[] = "title = '$title'";
if($description) $update_fields[] = "description = '$description'";
if($price) $update_fields[] = "price = '$price'";
if($image_urls !== null) $update_fields[] = "image_url = '$image_urls'";
if($latitude !== null) $update_fields[] = "latitude = '$latitude'";
if($longitude !== null) $update_fields[] = "longitude = '$longitude'";
if($city) $update_fields[] = "city = '$city'";

// Check if at least one field is being updated
if(empty($update_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update. Provide at least one field to update.', 'received_fields' => array_keys($input)]);
    exit;
}

$update_fields[] = "updated_at = NOW()";
$update_sql = "UPDATE $table_name SET " . implode(', ', $update_fields) . " WHERE id = '$add_id' AND user_id = '$user_id'";

if($conn->query($update_sql)) {
    http_response_code(200);
    $response['success'] = true;
    $response['message'] = 'Exercise bike ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'table' => $table_name,
        'updated_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id
    ];
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update exercise bike ad';
    $response['error'] = $conn->error;
    $response['error_code'] = $conn->errno;
    $response['debug_sql'] = $update_sql;
    $response['debug_input'] = $input;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
