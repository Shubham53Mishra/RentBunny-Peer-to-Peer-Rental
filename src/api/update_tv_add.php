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

if($_SERVER['REQUEST_METHOD'] != 'PUT' && $_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only PUT or POST method allowed']);
    exit;
}

// Required field: add_id
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

// Check if add_id is provided
if(!isset($input['add_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: add_id', 'received_fields' => array_keys($input)]);
    exit;
}

$add_id = intval($input['add_id']);
$table_name = 'tv_adds';

// Check if ad exists and belongs to current user
$check_sql = "SELECT id, user_id FROM $table_name WHERE id = '$add_id'";
$check_result = $conn->query($check_sql);

if(!$check_result || $check_result->num_rows == 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ad not found']);
    exit;
}

$ad_row = $check_result->fetch_assoc();
if($ad_row['user_id'] != $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only update your own ads']);
    exit;
}

// Build update query with required fields - same as POST
$update_fields = [];
$updates = [];

// List of required fields to update
$required_fields = ['size', 'brand', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'];

// Check if all required fields are provided
$missing_fields = [];
foreach($required_fields as $field) {
    if(!isset($input[$field]) || (is_string($input[$field]) && empty($input[$field]))) {
        $missing_fields[] = $field;
    }
}

if(!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields', 'missing_fields' => $missing_fields, 'received_fields' => array_keys($input)]);
    exit;
}

// Sanitize and validate input
$size = mysqli_real_escape_string($conn, $input['size']);
$brand = mysqli_real_escape_string($conn, $input['brand']);
$product_type = mysqli_real_escape_string($conn, $input['product_type']);
$price_per_month = floatval($input['price_per_month']);
$security_deposit = floatval($input['security_deposit']);
$ad_title = mysqli_real_escape_string($conn, $input['ad_title']);
$description = mysqli_real_escape_string($conn, $input['description']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$city = mysqli_real_escape_string($conn, $input['city']);

// Validate numeric values
if($price_per_month <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
    exit;
}
if($latitude < -90 || $latitude > 90) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid latitude value']);
    exit;
}
if($longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid longitude value']);
    exit;
}
if($security_deposit < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'security_deposit must be greater than or equal to 0']);
    exit;
}

// Build title from product_type and brand
$title = "$product_type - $brand ($size)";

// Build update query
$updates[] = "`title` = '$title'";
$updates[] = "`description` = '$description'";
$updates[] = "`price` = '$price_per_month'";
$updates[] = "`city` = '$city'";
$updates[] = "`latitude` = '$latitude'";
$updates[] = "`longitude` = '$longitude'";
$updates[] = "`brand` = '$brand'";
$updates[] = "`product_type` = '$product_type'";
$updates[] = "`security_deposit` = '$security_deposit'";

$update_fields = [
    'size' => $size,
    'brand' => $brand,
    'product_type' => $product_type,
    'price_per_month' => $price_per_month,
    'security_deposit' => $security_deposit,
    'ad_title' => $ad_title,
    'description' => $description,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'city' => $city
];



// Add updated_at timestamp
$updates[] = "`updated_at` = NOW()";

// Build and execute update query
$update_sql = "UPDATE $table_name SET " . implode(', ', $updates) . " WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    // Fetch updated record to return in response
    $fetch_sql = "SELECT id, user_id, title, description, price as price_per_month, city, latitude, longitude, image_url, brand, product_type, security_deposit, created_at, updated_at FROM $table_name WHERE id = '$add_id'";
    $fetch_result = $conn->query($fetch_sql);
    $updated_record = $fetch_result ? $fetch_result->fetch_assoc() : null;
    
    $response['success'] = true;
    $response['message'] = 'TV ad updated successfully';
    $response['data'] = [
        'id' => $add_id,
        'user_id' => $user_id,
        'updated_fields' => $update_fields,
        'updated_record' => $updated_record,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update TV ad';
    $response['error'] = $conn->error;
    $response['debug_sql'] = $update_sql;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
