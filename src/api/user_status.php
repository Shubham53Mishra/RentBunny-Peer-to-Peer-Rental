<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'GET') {
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
    
    // Fetch user data
    $sql = "SELECT id, mobile, email_address FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    
    if($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Check if user is a Renter (has any active rental listings)
        // A Renter is someone who has posted items for rent
        $rental_tables = array(
            'petrol_bike_adds', 'electric_bike_adds', 'cycle_adds', 
            'car_adds', 'camera_adds', 'clothing_adds',
            'fitness_adds', 'furniture_adds', 'kitchen_adds',
            'medical_adds', 'music_adds', 'appliance_adds',
            'ac_adds', 'refrigerator_adds', 'washing_machine_adds',
            'purifier_adds', 'tv_adds', 'computer_adds', 'printer_adds',
            'bed_adds', 'sofa_adds', 'dining_adds', 'sidetable_adds'
        );
        
        $is_renter = false;
        
        // Check if user has any active listings in any table
        foreach($rental_tables as $table) {
            $table_name = mysqli_real_escape_string($conn, $table);
            $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table_name' LIMIT 1";
            $table_exists = $conn->query($check_table_sql);
            
            if($table_exists && $table_exists->num_rows > 0) {
                // Check if status column exists in the table
                $column_check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table_name' AND COLUMN_NAME = 'status' AND TABLE_SCHEMA = DATABASE()";
                $column_result = $conn->query($column_check_sql);
                
                // Build query based on whether status column exists
                if($column_result && $column_result->num_rows > 0) {
                    $check_sql = "SELECT COUNT(*) as count FROM $table_name WHERE user_id = '$user_id' AND status = 'active' LIMIT 1";
                } else {
                    $check_sql = "SELECT COUNT(*) as count FROM $table_name WHERE user_id = '$user_id' LIMIT 1";
                }
                
                $check_result = $conn->query($check_sql);
                if($check_result) {
                    $count_row = $check_result->fetch_assoc();
                    if($count_row['count'] > 0) {
                        $is_renter = true;
                        break;
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'User status fetched successfully';
        $response['data'] = array(
            'user_id' => $user['id'],
            'mobile' => $user['mobile'],
            'email' => $user['email_address'],
            'status' => $is_renter ? 'Renter' : 'User',
            'is_renter' => $is_renter
        );
        http_response_code(200);
    } else {
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Invalid token or user not found';
    }
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use GET request.';
}

echo json_encode($response);
$conn->close();
?>
