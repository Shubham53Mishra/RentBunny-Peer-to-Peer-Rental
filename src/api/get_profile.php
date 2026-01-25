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
    
    // Fetch user profile data
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
            WHERE token = '$token' AND token IS NOT NULL";
    
    $result = $conn->query($sql);
    
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
} else {
    http_response_code(405);
    $response['success'] = false;
    $response['message'] = 'Method not allowed. Use GET request.';
}

echo json_encode($response);
$conn->close();
?>
