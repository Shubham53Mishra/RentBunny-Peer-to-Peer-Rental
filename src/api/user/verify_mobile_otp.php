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

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$mobile = isset($data['mobile']) ? mysqli_real_escape_string($conn, trim($data['mobile'])) : '';
$otp = isset($data['otp']) ? mysqli_real_escape_string($conn, trim($data['otp'])) : '';

$errors = array();
if(empty($mobile)) {
    $errors[] = 'Mobile number is required';
}
if(empty($otp)) {
    $errors[] = 'OTP is required';
}

if(!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation errors', 'errors' => $errors]);
    exit;
}

// Validate mobile format (adjust pattern based on your requirement)
if(!preg_match('/^[0-9]{10}$/', $mobile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format']);
    exit;
}

// Verify OTP
$otp_query = "SELECT id FROM mobile_otp WHERE user_id = $user_id AND mobile = '$mobile' AND otp = '$otp' AND is_verified = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
$otp_result = $conn->query($otp_query);

if($otp_result->num_rows == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    exit;
}

$otp_row = $otp_result->fetch_assoc();
$otp_id = $otp_row['id'];

// Mark OTP as verified (for delete account flow)
$verify_query = "UPDATE mobile_otp SET is_verified = 1, verified_at = NOW() WHERE id = $otp_id";
if($conn->query($verify_query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Mobile OTP verified successfully. Account deletion approved.'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to verify OTP', 'error' => $conn->error]);
}
?>
