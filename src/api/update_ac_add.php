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
$table_name = 'ac_adds';

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

// List of required fields to update - same as POST (without image_urls)
$required_fields = ['power_requirements', 'capacity', 'brand', 'product_type', 'price_per_month', 'security_deposit', 'ad_title', 'description', 'latitude', 'longitude', 'city'];

// Check if at least some fields are provided
if(empty(array_filter($input, function($val, $key) use ($required_fields) {
    return in_array($key, $required_fields) && !empty($val);
}, ARRAY_FILTER_USE_BOTH))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one field must be provided to update', 'received_fields' => array_keys($input)]);
    exit;
}

// Sanitize and validate input - only update fields that are provided
$updates = [];
$update_fields = [];

if(isset($input['power_requirements']) && !empty($input['power_requirements'])) {
    $power_requirements = mysqli_real_escape_string($conn, $input['power_requirements']);
    $update_fields['power_requirements'] = $power_requirements;
}

if(isset($input['capacity']) && !empty($input['capacity'])) {
    $capacity = mysqli_real_escape_string($conn, $input['capacity']);
    $update_fields['capacity'] = $capacity;
}

if(isset($input['brand']) && !empty($input['brand'])) {
    $brand = mysqli_real_escape_string($conn, $input['brand']);
    $update_fields['brand'] = $brand;
}

if(isset($input['product_type']) && !empty($input['product_type'])) {
    $product_type = mysqli_real_escape_string($conn, $input['product_type']);
    $update_fields['product_type'] = $product_type;
}

if(isset($input['price_per_month']) && $input['price_per_month'] > 0) {
    $price_per_month = floatval($input['price_per_month']);
    if($price_per_month <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
        exit;
    }
    $update_fields['price_per_month'] = $price_per_month;
    $updates[] = "`price` = '$price_per_month'";
}

if(isset($input['security_deposit']) && $input['security_deposit'] >= 0) {
    $security_deposit = floatval($input['security_deposit']);
    $update_fields['security_deposit'] = $security_deposit;
    $updates[] = "`security_deposit` = '$security_deposit'";
}

if(isset($input['ad_title']) && !empty($input['ad_title'])) {
    $ad_title = mysqli_real_escape_string($conn, $input['ad_title']);
    $update_fields['ad_title'] = $ad_title;
}

if(isset($input['description']) && !empty($input['description'])) {
    $description = mysqli_real_escape_string($conn, $input['description']);
    $update_fields['description'] = $description;
    $updates[] = "`description` = '$description'";
}

if(isset($input['latitude'])) {
    $latitude = floatval($input['latitude']);
    if($latitude < -90 || $latitude > 90) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid latitude value']);
        exit;
    }
    $update_fields['latitude'] = $latitude;
    $updates[] = "`latitude` = '$latitude'";
}

if(isset($input['longitude'])) {
    $longitude = floatval($input['longitude']);
    if($longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid longitude value']);
        exit;
    }
    $update_fields['longitude'] = $longitude;
    $updates[] = "`longitude` = '$longitude'";
}

if(isset($input['city']) && !empty($input['city'])) {
    $city = mysqli_real_escape_string($conn, $input['city']);
    $update_fields['city'] = $city;
    $updates[] = "`city` = '$city'";
}

// If brand or capacity changed, update title
if(isset($brand) || isset($capacity) || isset($product_type)) {
    $current_brand = isset($brand) ? $brand : $ad_row['brand'];
    $current_capacity = isset($capacity) ? $capacity : $ad_row['capacity'];
    $current_product_type = isset($product_type) ? $product_type : $ad_row['product_type'];
    $title = "$current_product_type - $current_brand ($current_capacity)";
    $updates[] = "`title` = '$title'";
}

if(isset($brand)) {
    $updates[] = "`brand` = '$brand'";
}

if(isset($product_type)) {
    $updates[] = "`product_type` = '$product_type'";
}



// Add updated_at timestamp
$updates[] = "`updated_at` = NOW()";

// Check if any fields were provided for update
if(count($updates) <= 1) {  // Only updated_at was added
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one field must be provided to update', 'valid_fields' => $required_fields]);
    exit;
}

// Build and execute update query
$update_sql = "UPDATE $table_name SET " . implode(', ', $updates) . " WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    // Fetch updated record to return in response
    $fetch_sql = "SELECT id, user_id, title, description, price as price_per_month, city, latitude, longitude, image_url, brand, product_type, security_deposit, created_at, updated_at FROM $table_name WHERE id = '$add_id'";
    $fetch_result = $conn->query($fetch_sql);
    $updated_record = $fetch_result ? $fetch_result->fetch_assoc() : null;
    
    $response['success'] = true;
    $response['message'] = 'AC ad updated successfully';
    $response['data'] = [
        'id' => $add_id,
        'user_id' => $user_id,
        'updated_fields' => $update_fields,
        'updated_record' => $updated_record,
        'updated_at' => date('Y-m-d H:i:s'),
        'note' => 'To update images, use POST to /api/image_upload.php with add_id=' . $add_id
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update AC ad';
    $response['error'] = $conn->error;
    $response['debug_sql'] = $update_sql;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
