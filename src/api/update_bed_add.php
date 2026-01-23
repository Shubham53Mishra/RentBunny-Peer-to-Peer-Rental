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

// Required fields for bed ad update
$required_fields = ['add_id'];

// Get JSON data
$raw_input = file_get_contents('php://input');

// Try to parse as JSON first
$input = json_decode($raw_input, true);

// If JSON parsing fails, try to parse as form-encoded data
if($input === null && !empty($raw_input)) {
    parse_str($raw_input, $input);
}

// If still no data, check $_POST
if(empty($input) && !empty($_POST)) {
    $input = $_POST;
}

// Check if we have any data at all
if(empty($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'No data received',
        'debug' => [
            'raw_input_empty' => empty($raw_input),
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
    exit;
}

foreach($required_fields as $field) {
    if(!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field", 'received_fields' => array_keys($input)]);
        exit;
    }
}

// Sanitize input and map to existing table columns
$add_id = intval($input['add_id']);
$frame_material = isset($input['frame_material']) ? mysqli_real_escape_string($conn, $input['frame_material']) : null;
$storage_included = isset($input['storage_included']) ? intval($input['storage_included']) : null;
$product_type = isset($input['product_type']) ? mysqli_real_escape_string($conn, $input['product_type']) : null;
$price_per_month = isset($input['price_per_month']) ? floatval($input['price_per_month']) : null;
$description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : null;

$table_name = 'bed_adds';

// Verify ownership
$verify_sql = "SELECT id FROM $table_name WHERE id = '$add_id' AND user_id = '$user_id'";
$verify_result = $conn->query($verify_sql);
if($verify_result->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ad']);
    exit;
}

// Build update query with only provided fields
$update_fields = [];

if($frame_material !== null || $storage_included !== null || $product_type !== null) {
    // If any title-related field is provided, rebuild the title
    if($product_type !== null) {
        $title = $product_type;
        if($frame_material !== null) {
            $title .= " - $frame_material";
        }
        if($storage_included) {
            $title .= ' (With Storage)';
        }
        $update_fields[] = "title = '$title'";
    } elseif($frame_material !== null || $storage_included !== null) {
        // Get existing data to preserve what's not being updated
        $get_sql = "SELECT title FROM $table_name WHERE id = '$add_id'";
        $get_result = $conn->query($get_sql);
        if($get_result && $get_result->num_rows > 0) {
            $existing = $get_result->fetch_assoc();
            $title = $existing['title'];
            if($frame_material !== null) {
                // Try to preserve product_type and update frame_material
                $parts = explode(' - ', $title);
                $title = $parts[0] . " - $frame_material";
                if($storage_included) {
                    $title .= ' (With Storage)';
                }
            }
            $update_fields[] = "title = '$title'";
        }
    }
}

if($price_per_month !== null) {
    $update_fields[] = "price = '$price_per_month'";
}

if($description !== null) {
    $update_fields[] = "description = '$description'";
}

if(empty($update_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields provided to update']);
    exit;
}

$update_fields[] = "updated_at = NOW()";

// Update database using existing table columns
$update_sql = "UPDATE $table_name SET " . implode(', ', $update_fields) . " WHERE id = '$add_id' AND user_id = '$user_id'";

if($conn->query($update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Bed ad updated successfully';
    $response['data'] = [
        'add_id' => $add_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to update bed ad: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
