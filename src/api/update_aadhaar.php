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
    $aadhaar_no = isset($_POST['aadhaar_no']) ? mysqli_real_escape_string($conn, $_POST['aadhaar_no']) : '';
    
    // Validate required field
    if(empty($aadhaar_no)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'aadhaar_no is required';
        echo json_encode($response);
        exit;
    }
    
    // Validate Aadhaar format (12 digits, X for masked)
    if(!preg_match('/^[\dX]{12}$/', str_replace(' ', '', $aadhaar_no))) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Invalid Aadhaar number format (should be 12 digits or X for masked)';
        echo json_encode($response);
        exit;
    }
    
    // Validate file upload
    if(!isset($_FILES['aadhaar_image']) || $_FILES['aadhaar_image']['error'] != UPLOAD_ERR_OK) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Invalid file or file not provided';
        echo json_encode($response);
        exit;
    }
    
    $file = $_FILES['aadhaar_image'];
    
    // Validate file type (PNG only)
    $allowed_types = array('image/png');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if(!in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'Only PNG images are allowed for Aadhaar';
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
    $documents_dir = '../../assets/documents/aadhaar/';
    if(!is_dir($documents_dir)) {
        mkdir($documents_dir, 0777, true);
    }
    
    // Generate unique filename with aadhaar_no and timestamp
    // Replace X with empty for filename to avoid issues
    $filename_aadhaar = str_replace('X', '', $aadhaar_no);
    $filename = $filename_aadhaar . '_' . time() . '.png';
    $filepath = $documents_dir . $filename;
    $relative_path = 'assets/documents/aadhaar/' . $filename;
    
    // Move uploaded file
    if(!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Failed to upload file';
        echo json_encode($response);
        exit;
    }
    
    // Check if user already has document record
    $check_sql = "SELECT id FROM user_documents WHERE user_id = '$user_id'";
    $check_result = $conn->query($check_sql);
    
    if($check_result && $check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE user_documents 
                       SET aadhaar_no = '$aadhaar_no', 
                           aadhaar_file_path = '$relative_path', 
                           aadhaar_verified = 1,
                           aadhaar_uploaded_at = NOW()
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
        $insert_sql = "INSERT INTO user_documents (user_id, aadhaar_no, aadhaar_file_path, aadhaar_verified, aadhaar_uploaded_at) 
                       VALUES ('$user_id', '$aadhaar_no', '$relative_path', 1, NOW())";
        
        if(!$conn->query($insert_sql)) {
            http_response_code(500);
            $response['success'] = false;
            $response['message'] = 'Database error: ' . $conn->error;
            echo json_encode($response);
            exit;
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Aadhaar uploaded and verified successfully';
    $response['data'] = array(
        'aadhaar_no' => $aadhaar_no,
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
