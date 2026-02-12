<?php
header('Content-Type: application/json');

// Dynamic path resolution - works on both local and live server
$current_dir = dirname(__FILE__);
$db_file = null;

// Try multiple common paths
$possible_paths = array(
    $current_dir . '/../../common/db.php',      // Local: src/api/user -> common
    $current_dir . '/../common/db.php',          // Live: user/common
    dirname(dirname($current_dir)) . '/common/db.php',  // Alternative
    dirname(dirname(dirname(dirname($current_dir)))) . '/config/database.php'  // Config db
);

foreach($possible_paths as $path) {
    if(file_exists($path)) {
        $db_file = $path;
        break;
    }
}

if(!$db_file) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection file not found']);
    exit;
}

require_once($db_file);

$response = array();

// Define absolute path - works for both local and live server
$base_path = dirname($current_dir);
while (!is_dir($base_path . '/assets') && $base_path != '/') {
    $base_path = dirname($base_path);
}

// Function to ensure directory exists
function ensure_directory_exists($dir_path) {
    if(!is_dir($dir_path)) {
        if(!mkdir($dir_path, 0755, true)) {
            return false;
        }
    }
    if(!is_writable($dir_path)) {
        @chmod($dir_path, 0755);
    }
    return is_writable($dir_path);
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
$sql = "SELECT id FROM users WHERE token = ? AND token IS NOT NULL";
$stmt = $conn->prepare($sql);
if(!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    $stmt->close();
    exit;
}

$user_row = $result->fetch_assoc();
$user_id = $user_row['id'];
$stmt->close();

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Get form data
$email_address = isset($_POST['email_address']) ? mysqli_real_escape_string($conn, trim($_POST['email_address'])) : '';
$full_name = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, trim($_POST['full_name'])) : '';
$address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : '';

// Validate required fields
$errors = array();
if(empty($email_address)) {
    $errors[] = 'Email address is required';
}
if(empty($full_name)) {
    $errors[] = 'Full name is required';
}
if(empty($address)) {
    $errors[] = 'Address is required';
}

// Validate email format
if(!empty($email_address) && !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Check if email is already registered by another user
if(!empty($email_address)) {
    $check_email = "SELECT id FROM users WHERE email_address = ? AND id != ?";
    $check_stmt = $conn->prepare($check_email);
    if($check_stmt) {
        $check_stmt->bind_param("si", $email_address, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if($check_result && $check_result->num_rows > 0) {
            $errors[] = 'Email already registered';
        }
        $check_stmt->close();
    }
}

if(!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation errors', 'errors' => $errors]);
    exit;
}

// Handle profile image upload if provided
$profile_image_path = '';
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = array('jpg', 'jpeg', 'gif', 'png');
    $filename = $_FILES['profile_image']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if(!in_array(strtolower($ext), $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only jpg, jpeg, gif, png allowed']);
        exit;
    }
    
    if($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    // Create user-specific uploads directory if not exists
    $upload_dir = $base_path . '/assets/uploads/users/' . $user_id . '/';
    
    // Ensure all directories exist with proper permissions
    if(!ensure_directory_exists($upload_dir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create or access upload directory']);
        exit;
    }
    
    // Generate unique filename
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;
    
    if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
        // Store relative path for web access - user-specific folder
        $profile_image_path = 'assets/uploads/users/' . $user_id . '/' . $new_filename;
        error_log("Image uploaded successfully: " . $upload_path);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload profile image', 'debug' => 'move_uploaded_file failed']);
        exit;
    }
}

// Prepare update query
$update_fields = array();
$update_fields[] = "email_address = ?";
$update_fields[] = "full_name = ?";
$update_fields[] = "address = ?";
$update_values = array($email_address, $full_name, $address);
$update_types = "sss";

if(!empty($profile_image_path)) {
    // Get old image path to delete
    $old_image_sql = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($old_image_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $old_image_result = $stmt->get_result();
    
    if($old_image_result && $old_image_result->num_rows > 0) {
        $old_image_row = $old_image_result->fetch_assoc();
        if(!empty($old_image_row['profile_image'])) {
            $old_path = $base_path . '/' . $old_image_row['profile_image'];
            if(file_exists($old_path)) {
                if(unlink($old_path)) {
                    error_log("Old image deleted: " . $old_path);
                } else {
                    error_log("Failed to delete old image: " . $old_path);
                }
            }
        }
    }
    $stmt->close();
    
    $update_fields[] = "profile_image = ?";
    $update_values[] = $profile_image_path;
    $update_types .= "s";
}

$update_fields[] = "updated_at = NOW()";

$update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
$update_values[] = $user_id;
$update_types .= "i";

// Use prepared statement for security
$stmt = $conn->prepare($update_query);
if(!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $conn->error]);
    exit;
}

$stmt->bind_param($update_types, ...$update_values);

if($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile', 'error' => $stmt->error]);
}
$stmt->close();
?>
