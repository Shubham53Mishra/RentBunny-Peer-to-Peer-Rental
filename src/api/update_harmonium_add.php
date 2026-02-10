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
$table_name = 'harmonium_adds';

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
$allowed_fields = ['title', 'description', 'price_per_month', 'security_deposit', 'city', 'latitude', 'longitude', 'brand', 'product_type'];

foreach($allowed_fields as $field) {
    if(isset($input[$field])) {
        $value = $input[$field];
        
        // Type-specific sanitization
        if(in_array($field, ['price_per_month', 'security_deposit', 'latitude', 'longitude'])) {
            $value = floatval($value);
        } else {
            $value = mysqli_real_escape_string($conn, $value);
        }
        
        // Validate numeric values
        if($field === 'price_per_month' && $value <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'price_per_month must be greater than 0']);
            exit;
        }
        if($field === 'security_deposit' && $value < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'security_deposit cannot be negative']);
            exit;
        }
        if($field === 'latitude' && ($value < -90 || $value > 90)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid latitude value']);
            exit;
        }
        if($field === 'longitude' && ($value < -180 || $value > 180)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid longitude value']);
            exit;
        }
        
        // Map price_per_month to price column in database
        $col_name = ($field === 'price_per_month') ? 'price' : $field;
        $updates[] = "`$col_name` = '$value'";
        $update_fields[$field] = $value;
    }
}

// Check if at least one field is being updated
if(empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update. Provide at least one field to update.']);
    exit;
}

// Add updated_at timestamp
$updates[] = "`updated_at` = NOW()";

// Build and execute update query
$update_sql = "UPDATE $table_name SET " . implode(', ', $updates) . " WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Harmonium ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'table' => $table_name,
        'updated_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'updated_fields' => array_keys($update_fields),
        'field_values' => $update_fields,
        'note' => 'To update images, use image_upload.php endpoint'
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update harmonium ad';
    $response['error'] = $conn->error;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
