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

// Check if files are uploaded
if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image file provided']);
    exit;
}

// Validate required fields
$required_fields = ['add_id', 'type', 'sub_type', 'chunkNumber', 'totalChunks', 'image_count'];
foreach($required_fields as $field) {
    if(!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$add_id = mysqli_real_escape_string($conn, $_POST['add_id']);
$type = mysqli_real_escape_string($conn, $_POST['type']);
$sub_type = mysqli_real_escape_string($conn, $_POST['sub_type']);
$chunkNumber = intval($_POST['chunkNumber']);
$totalChunks = intval($_POST['totalChunks']);
$image_count = intval($_POST['image_count']);

// Construct table name - if type already ends with _adds, use as-is, otherwise append
$table_name = (strpos($type, '_adds') !== false) ? $type : $type . '_adds';

// Check if table exists
$check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
$table_exists = $conn->query($check_table_sql);
if(!$table_exists || $table_exists->num_rows == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Table '$table_name' does not exist"]);
    exit;
}

// Validate add_id ownership
$verify_sql = "SELECT id FROM $table_name WHERE id = '$add_id' AND user_id = '$user_id'";
$verify_result = $conn->query($verify_sql);

if($verify_result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
    exit;
}

if($verify_result->num_rows == 0) {
    // Check if add exists but belongs to different user
    $check_ad_sql = "SELECT user_id FROM $table_name WHERE id = '$add_id'";
    $check_ad_result = $conn->query($check_ad_sql);
    
    if($check_ad_result && $check_ad_result->num_rows > 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: This ad belongs to another user']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Ad with ID '$add_id' not found in table '$table_name'"]);
    }
    exit;
}

// Create upload directory if not exists
$upload_dir = "../../assets/uploads/{$table_name}/{$add_id}/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
foreach($_FILES['image']['tmp_name'] as $key => $tmp_name) {
    if($_FILES['image']['error'][$key] != UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }

    $file_extension = pathinfo($_FILES['image']['name'][$key], PATHINFO_EXTENSION);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if(!in_array(strtolower($file_extension), $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only jpg, jpeg, png, gif allowed']);
        exit;
    }

    $filename = $add_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;

    if(move_uploaded_file($tmp_name, $upload_path)) {
        $uploaded_files[] = [
            'filename' => $filename,
            'path' => $upload_path,
            'url' => "/{$table_name}/{$add_id}/{$filename}"
        ];
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
}

// Store image records in database
$image_table = "{$table_name}_images";
foreach($uploaded_files as $file_data) {
    $filename = mysqli_real_escape_string($conn, $file_data['filename']);
    $insert_sql = "INSERT INTO $image_table (add_id, image_path, created_at) 
                   VALUES ('$add_id', '$filename', NOW())";
    $conn->query($insert_sql);
}

$response['success'] = true;
$response['message'] = 'Images uploaded successfully';
$response['data'] = [
    'files_uploaded' => count($uploaded_files),
    'chunk_number' => $chunkNumber,
    'total_chunks' => $totalChunks,
    'files' => $uploaded_files
];

echo json_encode($response);
$conn->close();
?>
