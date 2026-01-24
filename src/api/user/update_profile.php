<?php
header('Content-Type: application/json');
require_once('../../common/db.php');

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
    $check_email = "SELECT id FROM users WHERE email_address = '$email_address' AND id != $user_id";
    $check_result = $conn->query($check_email);
    if($check_result && $check_result->num_rows > 0) {
        $errors[] = 'Email already registered';
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
    
    // Create uploads directory if not exists
    $upload_dir = '../../../assets/uploads/profiles/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;
    
    if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
        $profile_image_path = 'assets/uploads/profiles/' . $new_filename;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload profile image']);
        exit;
    }
}

// Prepare update query
$update_fields = array();
$update_fields[] = "email_address = '$email_address'";
$update_fields[] = "full_name = '$full_name'";
$update_fields[] = "address = '$address'";

if(!empty($profile_image_path)) {
    // Get old image path to delete
    $old_image_sql = "SELECT profile_image FROM users WHERE id = $user_id";
    $old_image_result = $conn->query($old_image_sql);
    if($old_image_result && $old_image_result->num_rows > 0) {
        $old_image_row = $old_image_result->fetch_assoc();
        if(!empty($old_image_row['profile_image'])) {
            $old_path = '../../../' . $old_image_row['profile_image'];
            if(file_exists($old_path)) {
                unlink($old_path);
            }
        }
    }
    $update_fields[] = "profile_image = '$profile_image_path'";
}

$update_fields[] = "updated_at = NOW()";

$update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = $user_id";

if($conn->query($update_query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile', 'error' => $conn->error]);
}
?>
