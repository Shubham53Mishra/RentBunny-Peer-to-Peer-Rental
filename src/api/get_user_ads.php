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
    
    // Fetch all ads posted by user from all tables
    $ads = array();
    
    // Define all ad tables with their categories
    // Using individual tables since parent categories don't have single tables
    $ad_tables = array(
        // Vehicles
        'bike_adds' => 'Bike',
        'car_adds' => 'Car',
        'cycle_adds' => 'Cycle',
        
        // Furniture (multiple sub-tables)
        'bed_adds' => 'Furniture',
        'sofa_adds' => 'Furniture',
        'dining_adds' => 'Furniture',
        'sidetable_adds' => 'Furniture',
        
        // Appliances (multiple sub-tables)
        'ac_adds' => 'Appliances',
        'computer_adds' => 'Appliances',
        'printer_adds' => 'Appliances',
        'refrigerator_adds' => 'Appliances',
        'tv_adds' => 'Appliances',
        'washing_machine_adds' => 'Appliances',
        'purifier_adds' => 'Appliances',
        
        // Kitchen (multiple sub-tables)
        'chimney_adds' => 'Kitchen',
        'hob_adds' => 'Kitchen',
        
        // Music (multiple sub-tables)
        'guitar_adds' => 'Music',
        'drum_adds' => 'Music',
        'harmonium_adds' => 'Music',
        'keyboard_adds' => 'Music',
        'tabla_adds' => 'Music',
        'cajon_adds' => 'Music',
        
        // Fitness (multiple sub-tables)
        'treadmill_adds' => 'Fitness',
        'massager_adds' => 'Fitness',
        'excercise_bike_adds' => 'Fitness',
        'cross_trainer_adds' => 'Fitness',
        
        // Clothing
        'clothing_adds' => 'Clothing',
        
        // Camera
        'camera_adds' => 'Camera',
        
        // Medical (multiple sub-tables)
        'medical_adds' => 'Medical',
        'crutches_adds' => 'Medical',
        'wheelchair_adds' => 'Medical'
    );
    
    foreach($ad_tables as $table => $category) {
        // Check if table exists
        $table_exists_query = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table'";
        $table_exists_result = $conn->query($table_exists_query);
        
        if(!$table_exists_result || $table_exists_result->num_rows == 0) {
            // Table doesn't exist, skip it
            continue;
        }
        
        // Check if status and image_url columns exist in the table
        $columns_query = "SHOW COLUMNS FROM $table LIKE 'status'";
        $columns_result = $conn->query($columns_query);
        $has_status = ($columns_result && $columns_result->num_rows > 0);
        
        $image_query = "SHOW COLUMNS FROM $table LIKE 'image_url'";
        $image_result = $conn->query($image_query);
        $has_image_url = ($image_result && $image_result->num_rows > 0);
        
        // Build query based on column availability
        $select_fields = "id, title, description, price";
        
        if($has_image_url) {
            $select_fields .= ", image_url";
        } else {
            $select_fields .= ", NULL as image_url";
        }
        
        if($has_status) {
            $select_fields .= ", status";
        } else {
            $select_fields .= ", 'Active' as status";
        }
        
        $select_fields .= ", created_at";
        
        $query = "SELECT $select_fields FROM $table WHERE user_id = '$user_id' ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['category'] = $category;
                $row['table'] = $table;
                $ads[] = $row;
            }
        }
    }
    
    // Sort all ads by created_at descending
    usort($ads, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $response['success'] = true;
    $response['message'] = 'User ads retrieved successfully';
    $response['data'] = array(
        'total_ads' => count($ads),
        'ads' => $ads
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
