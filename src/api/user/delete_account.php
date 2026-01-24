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
$sql = "SELECT id, profile_image FROM users WHERE token = '$token' AND token IS NOT NULL";
$result = $conn->query($sql);
if($result->num_rows == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user_row = $result->fetch_assoc();
$user_id = $user_row['id'];
$profile_image = $user_row['profile_image'];

if($_SERVER['REQUEST_METHOD'] != 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only DELETE method allowed']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get all user data before deletion (for archiving)
    $user_data_query = "SELECT * FROM users WHERE id = $user_id";
    $user_data_result = $conn->query($user_data_query);
    $user_data = $user_data_result->fetch_assoc();
    
    // Archive user data in deleted_users table (save username/mobile)
    $mobile = mysqli_real_escape_string($conn, $user_data['mobile']);
    $email = isset($user_data['email_address']) ? mysqli_real_escape_string($conn, $user_data['email_address']) : '';
    $full_name = isset($user_data['full_name']) ? mysqli_real_escape_string($conn, $user_data['full_name']) : '';
    $archived_json = mysqli_real_escape_string($conn, json_encode($user_data));
    
    $archive_query = "INSERT INTO deleted_users (original_user_id, mobile, email_address, full_name, deleted_at, archived_data) 
                      VALUES ($user_id, '$mobile', '$email', '$full_name', NOW(), '$archived_json')";
    if(!$conn->query($archive_query)) {
        throw new Exception("Failed to archive user data: " . $conn->error);
    }
    
    // Delete user profile image if exists
    if(!empty($profile_image)) {
        $old_path = '../../../' . $profile_image;
        if(file_exists($old_path)) {
            unlink($old_path);
        }
    }
    
    // Delete all product ads from all tables first (to avoid foreign key constraint)
    $product_tables = array(
        'ac_adds', 'bike_adds', 'camera_adds', 'car_adds', 'clothing_adds', 'cycle_adds',
        'fitness_adds', 'furniture_adds', 'kitchen_adds', 'medical_adds', 'music_adds',
        'appliance_adds', 'bed_adds', 'cajon_adds', 'chimney_adds', 'computer_adds',
        'crosstrainer_adds', 'crutches_adds', 'dining_adds', 'drum_adds', 'exercise_bike_adds',
        'guitar_adds', 'harmonium_adds', 'hob_adds', 'keyboard_adds', 'laptop_adds',
        'massager_adds', 'mixer_adds', 'mobile_adds', 'printer_adds', 'purifier_adds',
        'refrigerator_adds', 'sidetable_adds', 'sofa_adds', 'tabla_adds', 'treadmill_adds',
        'tv_adds', 'violin_adds', 'washing_machine_adds', 'wheelchair_adds'
    );
    
    foreach($product_tables as $table) {
        // Check if table exists first
        $check_table = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'";
        $table_exists = $conn->query($check_table);
        
        if($table_exists && $table_exists->num_rows > 0) {
            $delete_ads = "DELETE FROM $table WHERE user_id = $user_id";
            if(!$conn->query($delete_ads)) {
                error_log("Warning: Failed to delete from $table: " . $conn->error);
            }
        }
    }
    
    // Delete all related OTP records
    $delete_email_otp = "DELETE FROM email_otp WHERE user_id = $user_id";
    if(!$conn->query($delete_email_otp)) {
        throw new Exception("Failed to delete email OTP: " . $conn->error);
    }
    
    $delete_mobile_otp = "DELETE FROM mobile_otp WHERE user_id = $user_id";
    if(!$conn->query($delete_mobile_otp)) {
        throw new Exception("Failed to delete mobile OTP: " . $conn->error);
    }
    
    // Delete user account from users table
    $delete_query = "DELETE FROM users WHERE id = $user_id";
    if(!$conn->query($delete_query)) {
        throw new Exception("Failed to delete user: " . $conn->error);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Account deleted successfully. All ads and user data archived.'
    ]);
} catch(Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete account', 'error' => $e->getMessage()]);
}
?>
