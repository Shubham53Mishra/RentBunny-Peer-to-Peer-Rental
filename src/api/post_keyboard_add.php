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

// Required fields for Keyboard ad
$required_fields = ['brand', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'city', 'latitude', 'longitude', 'image_urls'];

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if($input === null && file_get_contents('php://input') !== '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON format']);
    exit;
}

if(!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No JSON data received']);
    exit;
}

foreach($required_fields as $field) {
    if(!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize input
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
$string_fields = ['brand', 'product_type', 'ad_title', 'description', 'city'];
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


// Handle multiple image URLs as JSON array
if(!is_array($input['image_urls']) || empty($input['image_urls'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'image_urls must be a non-empty array']);
    exit;
}

$image_urls = '';
if(is_array($input['image_urls'])) {
    $validated_urls = array();
    foreach($input['image_urls'] as $url) {
        if(filter_var($url, FILTER_VALIDATE_URL)) {
            $validated_urls[] = mysqli_real_escape_string($conn, $url);
        }
    }
    if(empty($validated_urls)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'image_urls must contain at least one valid URL']);
        exit;
    }
    $image_urls = json_encode($validated_urls);
}

// Map to existing table columns
$title = "$brand $product_type - $ad_title";

$table_name = 'keyboard_adds';

// Insert into database using existing table columns
$insert_sql = "INSERT INTO $table_name (user_id, title, description, price_per_month, security_deposit, city, latitude, longitude, image_url, brand, created_at, updated_at)
               VALUES ('$user_id', '$title', '$description', '$price_per_month', '$security_deposit', '$city', '$latitude', '$longitude', '$image_urls', '$brand', NOW(), NOW())";

if($conn->query($insert_sql)) {
    $add_id = $conn->insert_id;
    $response['success'] = true;
    $response['message'] = 'Keyboard ad created successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'table' => $table_name,
        'created_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'brand' => $brand,
        'product_type' => $product_type,
        'ad_title' => $ad_title,
        'description' => $description,
        'price_per_month' => $price_per_month,
        'security_deposit' => $security_deposit,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'image_urls' => $validated_urls
    ];
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to create keyboard ad: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
