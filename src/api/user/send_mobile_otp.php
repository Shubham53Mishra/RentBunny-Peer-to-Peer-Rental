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

// Validate mobile
$mobile = isset($data['mobile']) ? mysqli_real_escape_string($conn, trim($data['mobile'])) : '';

if(empty($mobile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mobile number is required']);
    exit;
}

// Validate mobile format (10 digits, starts with 6-9)
if(!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format']);
    exit;
}

// Generate OTP
$otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Check for recent OTP requests (rate limiting - 1 minute)
$recent_otp = "SELECT id FROM mobile_otp WHERE user_id = $user_id AND mobile = '$mobile' AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
$recent_result = $conn->query($recent_otp);
if($recent_result->num_rows > 0) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait before requesting another OTP']);
    exit;
}

// Store OTP in database
$insert_otp = "INSERT INTO mobile_otp (user_id, mobile, otp, created_at) VALUES ($user_id, '$mobile', '$otp', NOW())";
if($conn->query($insert_otp)) {
    // Send OTP via 2Factor.in API
    $sms_result = sendMobileOTP($mobile, $otp);
    
    if($sms_result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to mobile successfully',
            'otp' => $otp  // For testing - remove in production
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send OTP',
            'error' => $sms_result['error'],
            'api_response' => $sms_result['api_response']
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP', 'error' => $conn->error]);
}

// Function to send OTP via 2Factor.in API
function sendMobileOTP($mobile, $otp) {
    // 2Factor.in API credentials
    $apiKey = "e038e973-af07-11e9-ade6-0200cd936042";
    $sender = "RIDBYK";
    
    // Mobile number with country code
    $numbers = '91' . $mobile;
    
    // DLT approved message
    $message = $otp . ' is your One Time Password for account verification on RentBunny. Do not share this with anyone.';
    
    // 2Factor.in API URL
    $url = 'https://2factor.in/API/R1/';
    
    // API data
    $data = array(
        'module' => 'TRANS_SMS',
        'apikey' => $apiKey,
        'to' => $numbers,
        'from' => $sender,
        'msg' => $message
    );
    
    // Send SMS via cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log all details for debugging
    error_log("2Factor API Call Details:");
    error_log("Mobile: " . $numbers);
    error_log("HTTP Code: " . $http_code);
    error_log("cURL Error: " . $curl_error);
    error_log("API Response: " . $api_response);
    
    // Parse API response
    $api_json = json_decode($api_response, true);
    error_log("Parsed JSON: " . json_encode($api_json));
    
    // Check if SMS sent successfully
    if($http_code == 200 && isset($api_json['Status']) && $api_json['Status'] == 'Success') {
        return ['success' => true, 'error' => null, 'api_response' => $api_json];
    }
    
    // Return detailed error
    return [
        'success' => false, 
        'error' => $curl_error ?: (isset($api_json['Status']) ? $api_json['Status'] : 'Unknown error'),
        'api_response' => $api_response
    ];
}
?>
