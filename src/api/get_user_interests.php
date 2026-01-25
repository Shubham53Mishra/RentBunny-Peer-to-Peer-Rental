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
    
    // Check if user_interest table exists
    $check_table_sql = "SHOW TABLES LIKE 'user_interest'";
    $table_result = $conn->query($check_table_sql);
    
    if(!$table_result || $table_result->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `user_interest` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `add_id` INT NOT NULL,
            `category` VARCHAR(50),
            `interest_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_add_id` (`add_id`),
            CONSTRAINT `fk_user_interest_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_table_sql);
    }
    
    // Fetch all interests sent by user
    $interests_query = "SELECT id, add_id, category, interest_date FROM user_interest WHERE user_id = '$user_id' ORDER BY interest_date DESC";
    $interests_result = $conn->query($interests_query);
    
    $interests = array();
    if($interests_result && $interests_result->num_rows > 0) {
        while($row = $interests_result->fetch_assoc()) {
            $interests[] = $row;
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'User interests retrieved successfully';
    $response['data'] = array(
        'total_interests' => count($interests),
        'interests' => $interests
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
