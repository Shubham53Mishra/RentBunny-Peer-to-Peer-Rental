<?php
session_start();
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

// 2Factor.in API credentials
$apiKey = "e038e973-af07-11e9-ade6-0200cd936042";
$sender = "RIDBYK";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // ===== STEP 1: Send OTP =====
    if($action == 'send_otp') {
        if(isset($_POST['mob'])) {
            $mobile = mysqli_real_escape_string($conn, trim($_POST['mob']));
            
            // Validate mobile number (10 digits)
            if(!preg_match('/^[6-9]\d{9}$/', $mobile)) {
                $response['status'] = false;
                $response['message'] = 'Invalid mobile number format';
                echo json_encode($response);
                exit;
            }
            
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Calculate expiry time (10 minutes from now)
            $expires_at = date('Y-m-d H:i:s', time() + 600);
            
            // Check if mobile number exists in users table
            $check_sql = "SELECT id FROM users WHERE mobile = '$mobile'";
            $check_result = $conn->query($check_sql);
            
            if($check_result->num_rows > 0) {
                // Mobile exists - Update OTP
                $update_sql = "UPDATE users SET otp = '$otp', otp_created_at = NOW(), otp_expires_at = '$expires_at' WHERE mobile = '$mobile'";
                
                if(!$conn->query($update_sql)) {
                    $response['status'] = false;
                    $response['message'] = 'Failed to update OTP: ' . $conn->error;
                    echo json_encode($response);
                    exit;
                }
            } else {
                // Mobile doesn't exist - Create new entry
                $insert_sql = "INSERT INTO users (mobile, otp, otp_created_at, otp_expires_at) 
                              VALUES ('$mobile', '$otp', NOW(), '$expires_at')";
                
                if(!$conn->query($insert_sql)) {
                    $response['status'] = false;
                    $response['message'] = 'Failed to save OTP: ' . $conn->error;
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Mobile number with country code
            $numbers = '91' . $mobile;
            
            // DLT approved message - EXACTLY like reference code
            $message = $otp . ' is your One Time Password to signup for Ridobiko. Don\'t share this with anyone.';
            
            // 2Factor.in API URL
            $url = 'https://2factor.in/API/R1/';
            
            // API data - EXACTLY like reference code
            $data = array(
                'module' => 'TRANS_SMS',
                'apikey' => $apiKey,
                'to' => $numbers,
                'from' => $sender,
                'msg' => $message
            );
            
            // Send SMS via cURL - EXACTLY like reference code
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $api_response = curl_exec($ch);
            curl_close($ch);
            
            // Parse API response
            $api_json = json_decode($api_response, true);
            
            // Check if SMS sent successfully
            if(isset($api_json['Status']) && $api_json['Status'] == 'Success') {
                $response['status'] = true;
                $response['message'] = 'OTP sent successfully to ' . $mobile;
                
                // Store session ID if available
                if(isset($api_json['Details'])) {
                    $response['session_id'] = $api_json['Details'];
                }
                
                // DEBUG: Uncomment below line for testing
                // $response['otp'] = $otp;
            } else {
                $response['status'] = false;
                $response['message'] = 'Failed to send OTP';
                $response['debug'] = array(
                    'api_status' => isset($api_json['Status']) ? $api_json['Status'] : 'Unknown',
                    'api_details' => isset($api_json['Details']) ? $api_json['Details'] : 'No details',
                    'api_response' => $api_response
                );
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Mobile number required';
        }
    }
    
    // ===== STEP 2: Verify OTP =====
    else if($action == 'verify_otp') {
        if(isset($_POST['otp']) && isset($_POST['mob'])) {
            $entered_otp = trim($_POST['otp']);
            $mobile = mysqli_real_escape_string($conn, trim($_POST['mob']));
            
            // Get OTP from database
            $sql = "SELECT otp, otp_expires_at FROM users 
                    WHERE mobile = '$mobile' 
                    AND otp IS NOT NULL";
            $result = $conn->query($sql);
            
            if($result->num_rows == 0) {
                $response['status'] = false;
                $response['message'] = 'OTP not found. Please request a new one.';
            } else {
                $row = $result->fetch_assoc();
                $stored_otp = trim($row['otp']);
                $expires_at = strtotime($row['otp_expires_at']);
                $now = time();
                
                // Check if OTP is expired
                if($now > $expires_at) {
                    $response['status'] = false;
                    $response['message'] = 'OTP expired. Please request a new one.';
                }
                // Check if OTP matches
                else if($entered_otp != $stored_otp) {
                    $response['status'] = false;
                    $response['message'] = 'Invalid OTP';
                } else {
                    // OTP verified successfully - Generate token
                    $token = bin2hex(random_bytes(32)); // Generate 64-character token
                    $update_sql = "UPDATE users SET otp = NULL, token = '$token', token_created_at = NOW() WHERE mobile = '$mobile'";
                    $conn->query($update_sql);
                    
                    $response['status'] = true;
                    $response['message'] = 'OTP verified successfully';
                    $response['mobile'] = $mobile;
                    $response['token'] = $token;
                }
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'OTP and mobile number required';
        }
    }
    
    else {
        $response['status'] = false;
        $response['message'] = 'Invalid action';
    }
} else {
    $response['status'] = false;
    $response['message'] = 'Only POST method allowed';
}

echo json_encode($response);
$conn->close();
?>