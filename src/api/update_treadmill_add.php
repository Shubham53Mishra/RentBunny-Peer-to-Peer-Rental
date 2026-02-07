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

if($_SERVER['REQUEST_METHOD'] != 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only PUT method allowed']);
    exit;
}

// Required fields for treadmill ad - same as POST API
$required_fields = ['add_id', 'brand', 'product_type', 'price_per_month', 'ad_title', 'description', 'latitude', 'longitude', 'city', 'image_url', 'security_deposit'];

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

$add_id = intval($input['add_id']);
$table_name = 'treadmill_adds';

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

// Handle image_url array
$image_urls = '';
if(isset($input['image_url']) && is_array($input['image_url'])) {
    $validated_urls = array();
    foreach($input['image_url'] as $url) {
        if(filter_var($url, FILTER_VALIDATE_URL)) {
            $validated_urls[] = mysqli_real_escape_string($conn, $url);
        }
    }
    $image_urls = !empty($validated_urls) ? json_encode($validated_urls) : '';
} else if(isset($input['image_url']) && is_string($input['image_url'])) {
    if(filter_var($input['image_url'], FILTER_VALIDATE_URL)) {
        $image_urls = json_encode([mysqli_real_escape_string($conn, $input['image_url'])]);
    }
}

if(empty($image_urls)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'image_url must be a valid URL or array of URLs']);
    exit;
}

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

// Update in database
$update_sql = "UPDATE $table_name SET 
               title = '$ad_title',
               description = '$description',
               price = '$price_per_month',
               city = '$city',
               latitude = '$latitude',
               longitude = '$longitude',
               image_url = '$image_urls',
               brand = '$brand',
               product_type = '$product_type',
               security_deposit = '$security_deposit',
               updated_at = NOW()
               WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Treadmill ad updated successfully';
    $response['data'] = [
        'id' => $add_id,
        'user_id' => $user_id,
        'ad_title' => $ad_title,
        'description' => $description,
        'price_per_month' => $price_per_month,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'image_url' => $image_urls,
        'brand' => $brand,
        'product_type' => $product_type,
        'security_deposit' => $security_deposit,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update treadmill ad';
    $response['error'] = $conn->error;
    $response['error_code'] = $conn->errno;
    $response['debug_sql'] = $update_sql;
    $response['debug_input'] = $input;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>