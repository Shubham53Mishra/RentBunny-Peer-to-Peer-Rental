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
$table_name = 'purifier_adds';

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

// Build update query with only provided fields
$update_fields = [];
$updates = [];

// List of allowed fields to update
$allowed_fields = ['purification_technology', 'capacity', 'brand', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'];

// Check which fields are provided
foreach($allowed_fields as $field) {
    if(isset($input[$field])) {
        $update_fields[$field] = $input[$field];
    }
}

// If no fields provided, return error
if(empty($update_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid fields provided to update', 'allowed_fields' => $allowed_fields]);
    exit;
}

// Sanitize and validate input for fields that are provided
$purification_technology = isset($input['purification_technology']) ? mysqli_real_escape_string($conn, $input['purification_technology']) : null;
$capacity = isset($input['capacity']) ? mysqli_real_escape_string($conn, $input['capacity']) : null;
$brand = isset($input['brand']) ? mysqli_real_escape_string($conn, $input['brand']) : null;
$product_type = isset($input['product_type']) ? mysqli_real_escape_string($conn, $input['product_type']) : null;
$price_per_month = isset($input['price_per_month']) ? floatval($input['price_per_month']) : null;
$security_deposit = isset($input['security_deposit']) ? floatval($input['security_deposit']) : null;
$ad_title = isset($input['ad_title']) ? mysqli_real_escape_string($conn, $input['ad_title']) : null;
$description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : null;
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
$city = isset($input['city']) ? mysqli_real_escape_string($conn, $input['city']) : null;

// Validate numeric values if provided
if($price_per_month !== null && $price_per_month <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
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
if($security_deposit !== null && $security_deposit < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'security_deposit must be greater than or equal to 0']);
    exit;
}

// Build update query only for provided fields
if($product_type !== null || $brand !== null || $capacity !== null) {
    $title = ($product_type ?? 'Product') . " - " . ($brand ?? 'Brand') . " (" . ($capacity ?? '') . ")";
    $updates[] = "`title` = '$title'";
}
if($description !== null) {
    $updates[] = "`description` = '$description'";
}
if($price_per_month !== null) {
    $updates[] = "`price` = '$price_per_month'";
}
if($city !== null) {
    $updates[] = "`city` = '$city'";
}
if($latitude !== null) {
    $updates[] = "`latitude` = '$latitude'";
}
if($longitude !== null) {
    $updates[] = "`longitude` = '$longitude'";
}
if($brand !== null) {
    $updates[] = "`brand` = '$brand'";
}
if($product_type !== null) {
    $updates[] = "`product_type` = '$product_type'";
}
if($security_deposit !== null) {
    $updates[] = "`security_deposit` = '$security_deposit'";
}



// Add updated_at timestamp
$updates[] = "`updated_at` = NOW()";

// Build and execute update query
$update_sql = "UPDATE $table_name SET " . implode(', ', $updates) . " WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    // Fetch updated record to return in response
    $fetch_sql = "SELECT id, user_id, title, description, price as price_per_month, city, latitude, longitude, image_url, brand, product_type, security_deposit, created_at, updated_at FROM $table_name WHERE id = '$add_id'";
    $fetch_result = $conn->query($fetch_sql);
    $updated_record = $fetch_result ? $fetch_result->fetch_assoc() : null;
    
    // Parse image_url to image_urls
    if($updated_record && isset($updated_record['image_url'])) {
        $updated_record['image_urls'] = json_decode($updated_record['image_url'], true) ?: [];
        unset($updated_record['image_url']);
    }
    
    $response['success'] = true;
    $response['message'] = 'Purifier ad updated successfully';
    $response['data'] = [
        'id' => intval($updated_record['id']),
        'user_id' => intval($updated_record['user_id']),
        'purification_technology' => $purification_technology,
        'capacity' => $capacity,
        'brand' => $brand,
        'product_type' => $product_type,
        'price_per_month' => floatval($updated_record['price_per_month']),
        'security_deposit' => $security_deposit,
        'ad_title' => $ad_title,
        'description' => $description,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'city' => $city,
        'image_urls' => $updated_record['image_urls'],
        'created_at' => $updated_record['created_at'],
        'updated_at' => $updated_record['updated_at']
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update purifier ad';
    $response['error'] = $conn->error;
    $response['error_code'] = $conn->errno;
    $response['debug_sql'] = $update_sql;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
