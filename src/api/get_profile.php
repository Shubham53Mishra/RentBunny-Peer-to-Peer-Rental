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
    
    // Fetch user profile data using prepared statement
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
            WHERE token = ? AND token IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response['success'] = true;
        $response['message'] = 'Profile fetched successfully';
        $response['data'] = array(
            'user_id' => $user['id'],
            'mobile' => $user['mobile'],
            'email' => $user['email_address'],
            'full_name' => $user['full_name'],
            'address' => $user['address'],
            'profile_image' => $user['profile_image'],
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
        http_response_code(401);
        $response['success'] = false;
        $response['message'] = 'Invalid token or user not found';
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
