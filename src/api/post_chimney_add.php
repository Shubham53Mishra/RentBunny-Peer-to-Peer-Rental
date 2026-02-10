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
    echo json_encode(['success' => false, 'message' => 'Invalid token', 'debug_token' => substr($token, 0, 10) . '...']);
    exit;
}

$user_row = $result->fetch_assoc();
$user_id = $user_row['id'];

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Required fields for Chimney ad
$required_fields = ['suction_capacity', 'model', 'voltage', 'filter_type', 'brand', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'];

// Get JSON data
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

foreach($required_fields as $field) {
    if(!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field", 'received_fields' => array_keys($input)]);
        exit;
    }
}

// Sanitize input
$suction_capacity = mysqli_real_escape_string($conn, $input['suction_capacity']);
$model = mysqli_real_escape_string($conn, $input['model']);
$voltage = mysqli_real_escape_string($conn, $input['voltage']);
$filter_type = mysqli_real_escape_string($conn, $input['filter_type']);
$brand = mysqli_real_escape_string($conn, $input['brand']);
$product_type = mysqli_real_escape_string($conn, $input['product_type']);
$price_per_month = floatval($input['price_per_month']);
$security_deposit = floatval($input['security_deposit']);
$ad_title = mysqli_real_escape_string($conn, $input['ad_title']);
$description = mysqli_real_escape_string($conn, $input['description']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$city = mysqli_real_escape_string($conn, $input['city']);

// Validate all string fields are not empty
$string_fields = ['suction_capacity', 'model', 'voltage', 'filter_type', 'brand', 'product_type', 'ad_title', 'description', 'city'];
foreach($string_fields as $field) {
    if(empty($$field) || strlen(trim($$field)) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field cannot be empty: $field"]);
        exit;
    }
}

// Validate numeric values
if(!isset($input['price_per_month']) || $input['price_per_month'] === '' || $input['price_per_month'] === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'price_per_month is required']);
    exit;
}
if($price_per_month <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
    exit;
}
if(!isset($input['security_deposit']) || $input['security_deposit'] === '' || $input['security_deposit'] === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'security_deposit is required']);
    exit;
}
if($security_deposit < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'security_deposit cannot be negative']);
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


// Images will be uploaded separately via image_upload.php
$image_urls = '';

// Map to existing table columns
$title = "$product_type - $brand ($model)";

$table_name = 'chimney_adds';

// Insert into database using existing table columns
$insert_sql = "INSERT INTO $table_name (user_id, title, description, price, security_deposit, city, latitude, longitude, brand, created_at, updated_at)
               VALUES ('$user_id', '$title', '$description', '$price_per_month', '$security_deposit', '$city', '$latitude', '$longitude', '$brand', NOW(), NOW())";

if($conn->query($insert_sql)) {
    $add_id = $conn->insert_id;
    $response['success'] = true;
    $response['message'] = 'Chimney ad posted successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'table' => $table_name,
        'created_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'suction_capacity' => $suction_capacity,
        'voltage' => $voltage,
        'filter_type' => $filter_type,
        'brand' => $brand,
        'product_type' => $product_type,
        'ad_title' => $ad_title,
        'description' => $description,
        'price_per_month' => $price_per_month,
        'security_deposit' => $security_deposit,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to post chimney ad';
    $response['error'] = $conn->error;
}

echo json_encode($response);
$conn->close();
?>
