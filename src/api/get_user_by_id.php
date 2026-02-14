<?php
header('Content-Type: application/json');

// Dynamic path resolution - works on both local and live server
$current_dir = dirname(__FILE__);
$db_file = null;

// Try multiple common paths
$possible_paths = array(
    $current_dir . '/../common/db.php',         // Local: src/api -> common
    $current_dir . '/../../common/db.php',      // Alternative local
    dirname(dirname($current_dir)) . '/common/db.php',  // Another option
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

// Function to get base URL
function get_base_url() {
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $url = $protocol . $host;
    return rtrim($url, '/');
}

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get user_id from query parameter
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    // Validate user_id
    if(empty($user_id) || $user_id <= 0) {
        http_response_code(400);
        $response['success'] = false;
        $response['message'] = 'User ID is required and must be a valid number';
        echo json_encode($response);
        exit;
    }
    
    // Fetch user details using prepared statement
    $sql = "SELECT 
                id,
                mobile,
                email_address,
                full_name,
                address,
                profile_image,
                phone,
                phone_verified,
                email_verified,
                city,
                latitude,
                longitude,
                created_at,
                updated_at
            FROM users 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response['success'] = true;
        $response['message'] = 'User details fetched successfully';
        
        // Build profile image URL
        $profile_image_url = null;
        if(!empty($user['profile_image'])) {
            $base_url = get_base_url();
            $profile_image_url = $base_url . '/' . $user['profile_image'];
        }
        
        $response['data'] = array(
            'user_id' => $user['id'],
            'mobile' => $user['mobile'],
            'email' => $user['email_address'],
            'full_name' => $user['full_name'],
            'address' => $user['address'],
            'profile_image' => $user['profile_image'],
            'profile_image_url' => $profile_image_url,
            'phone' => $user['phone'],
            'phone_verified' => (bool)$user['phone_verified'],
            'email_verified' => (bool)$user['email_verified'],
            'city' => $user['city'],
            'latitude' => $user['latitude'],
            'longitude' => $user['longitude'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at']
        );
        http_response_code(200);
    } else {
        http_response_code(404);
        $response['success'] = false;
        $response['message'] = 'User not found';
    }
    
    $stmt->close();
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use GET request.';
}

echo json_encode($response);
$conn->close();
?>
