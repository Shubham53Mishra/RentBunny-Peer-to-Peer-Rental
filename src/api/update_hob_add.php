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
$table_name = 'hob_adds';

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

// Build update query with all required fields
$update_fields = [];
$updates = [];

// List of required fields to update
$required_fields = ['title', 'description', 'price_per_month', 'city', 'latitude', 'longitude', 'brand', 'product_type', 'security_deposit', 'image_url'];

// Check if all required fields are provided
foreach($required_fields as $field) {
    if(!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field", 'received_fields' => array_keys($input)]);
        exit;
    }
}

foreach($required_fields as $field) {
    $value = $input[$field];
    
    // Check for NULL or empty values in string fields (before type conversion)
    if(!in_array($field, ['price_per_month', 'latitude', 'longitude', 'image_url', 'security_deposit'])) {
        // String field validation
        if($value === null || $value === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field cannot be NULL or empty: $field"]);
            exit;
        }
        if(is_string($value) && trim($value) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field cannot be empty: $field"]);
            exit;
        }
    }
    
    // Special handling for image_url array - REQUIRED and must have valid URLs
    if($field === 'image_url') {
        if(!is_array($value) || empty($value)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'image_url must be a non-empty array']);
            exit;
        }
        
        $validated_urls = array();
        foreach($value as $url) {
            if(filter_var($url, FILTER_VALIDATE_URL)) {
                $validated_urls[] = mysqli_real_escape_string($conn, $url);
            }
        }
        
        if(empty($validated_urls)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'image_url must contain at least one valid URL']);
            exit;
        }
        
        $image_urls = json_encode($validated_urls);
        $updates[] = "`image_url` = '$image_urls'";
        $update_fields[$field] = $value;
        continue;
    }
    
    // Type-specific sanitization
    if(in_array($field, ['price_per_month', 'latitude', 'longitude', 'security_deposit'])) {
        $value = floatval($value);
    } else {
        $value = mysqli_real_escape_string($conn, (string)$value);
    }
    
    // Validate that string fields are not empty after sanitization
    if(!in_array($field, ['price_per_month', 'latitude', 'longitude', 'image_url', 'security_deposit'])) {
        if(empty($value) || strlen(trim($value)) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Validation failed: $field cannot be empty after sanitization"]);
            exit;
        }
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
    
    $updates[] = "`$field` = '$value'";
    $update_fields[$field] = $value;
}

// Add updated_at timestamp
$updates[] = "`updated_at` = NOW()";

// Build and execute update query
$update_sql = "UPDATE $table_name SET " . implode(', ', $updates) . " WHERE id = '$add_id'";

if($conn->query($update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Hob ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'table' => $table_name,
        'updated_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'updated_fields' => array_keys($update_fields)
    ];
    http_response_code(200);
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update ' . $table_name;
    $response['error'] = $conn->error;
    $response['error_code'] = $conn->errno;
    $response['debug_sql'] = $update_sql;
    $response['debug_input'] = $input;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
