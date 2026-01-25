<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get token from multiple possible sources
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    
    if(empty($token) && function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = isset($headers['token']) ? $headers['token'] : (isset($headers['Token']) ? $headers['Token'] : '');
    }
    if(empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    if(empty($token) && isset($_SERVER['HTTP_TOKEN'])) {
        $token = $_SERVER['HTTP_TOKEN'];
    }
    
    // Validate token
    if(empty($token)) {
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Token not provided';
        echo json_encode($response);
        exit;
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    
    // Get user from token
    $sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    
    if(!$result || $result->num_rows == 0) {
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Invalid token';
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // Get form data
    $dl_no = isset($_POST['dl_no']) ? mysqli_real_escape_string($conn, $_POST['dl_no']) : '';
    
    // Validate required field
    if(empty($dl_no)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'dl_no is required';
        echo json_encode($response);
        exit;
    }
    
    // Validate file upload
    if(!isset($_FILES['dl_image']) || $_FILES['dl_image']['error'] != UPLOAD_ERR_OK) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Invalid file or file not provided';
        echo json_encode($response);
        exit;
    }
    
    $file = $_FILES['dl_image'];
    
    // Validate file type (PDF only)
    $allowed_types = array('application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if(!in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Only PDF files are allowed for Driving License';
        echo json_encode($response);
        exit;
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if($file['size'] > $max_size) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'File size exceeds 5MB limit';
        echo json_encode($response);
        exit;
    }
    
    // Create documents directory if not exists
    $documents_dir = '../../assets/documents/driving_license/';
    if(!is_dir($documents_dir)) {
        mkdir($documents_dir, 0777, true);
    }
    
    // Generate unique filename with dl_no and timestamp
    $filename = $dl_no . '_' . time() . '.pdf';
    $filepath = $documents_dir . $filename;
    $relative_path = 'assets/documents/driving_license/' . $filename;
    
    // Move uploaded file
    if(!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Failed to upload file';
        echo json_encode($response);
        exit;
    }
    
    // Check if DL number already exists (registered by any user)
    $check_dl_sql = "SELECT id, user_id FROM user_documents WHERE dl_no = '$dl_no'";
    $check_dl_result = $conn->query($check_dl_sql);
    
    if($check_dl_result && $check_dl_result->num_rows > 0) {
        $existing_record = $check_dl_result->fetch_assoc();
        
        // If DL exists but belongs to current user, update it
        if($existing_record['user_id'] == $user_id) {
            $update_sql = "UPDATE user_documents 
                           SET dl_file_path = '$relative_path', 
                               dl_verified = 1,
                               dl_uploaded_at = NOW()
                           WHERE user_id = '$user_id' AND dl_no = '$dl_no'";
            
            if(!$conn->query($update_sql)) {
                http_response_code(500);
                $response['success'] = false;
                $response['message'] = 'Database error: ' . $conn->error;
                echo json_encode($response);
                exit;
            }
        } else {
            // DL already registered by another user
            http_response_code(400);
            $response['success'] = false;
            $response['message'] = 'This Driving License number is already registered';
            echo json_encode($response);
            exit;
        }
    } else {
        // Check if user already has a document record
        $check_user_sql = "SELECT id FROM user_documents WHERE user_id = '$user_id'";
        $check_user_result = $conn->query($check_user_sql);
        
        if($check_user_result && $check_user_result->num_rows > 0) {
            // Update existing record with new DL
            $update_sql = "UPDATE user_documents 
                           SET dl_no = '$dl_no', 
                               dl_file_path = '$relative_path', 
                               dl_verified = 1,
                               dl_uploaded_at = NOW()
                           WHERE user_id = '$user_id'";
            
            if(!$conn->query($update_sql)) {
                http_response_code(500);
                $response['success'] = false;
                $response['message'] = 'Database error: ' . $conn->error;
                echo json_encode($response);
                exit;
            }
        } else {
            // Create new record
            $insert_sql = "INSERT INTO user_documents (user_id, dl_no, dl_file_path, dl_verified, dl_uploaded_at) 
                           VALUES ('$user_id', '$dl_no', '$relative_path', 1, NOW())";
            
            if(!$conn->query($insert_sql)) {
                http_response_code(500);
                $response['success'] = false;
                $response['message'] = 'Database error: ' . $conn->error;
                echo json_encode($response);
                exit;
            }
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Driving License uploaded and verified successfully';
    $response['data'] = array(
        'dl_no' => $dl_no,
        'file_path' => $relative_path,
        'verified' => true,
        'uploaded_at' => date('Y-m-d H:i:s')
    );
    http_response_code(200);
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use POST request.';
}

echo json_encode($response);
$conn->close();
?>
