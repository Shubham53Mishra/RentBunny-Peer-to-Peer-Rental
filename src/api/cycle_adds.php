<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
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
    if(empty($token)) {
        $response['success'] = false;
        $response['message'] = 'Token not provided';
        echo json_encode($response);
        exit;
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    $sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
    $result = $conn->query($sql);
    if($result->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'Invalid token';
        echo json_encode($response);
        exit;
    }
    
    // Check if cycle_adds table exists
    $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'cycle_adds' LIMIT 1";
    $table_exists = $conn->query($check_table_sql);
    
    $grouped_products = array(
        'Road Bike' => array(),
        'Cruiser' => array(),
        'Fixed Gear Bike' => array(),
        'Mountain Bike' => array(),
        'BMX' => array(),
        'Touring Bike' => array(),
        'Folding Bike' => array(),
        'Electrical Bike' => array(),
        'Cyclocross Bike' => array(),
        'Hybrid Bike' => array(),
        'Others' => array()
    );
    
    if($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT * FROM cycle_adds ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Determine category based on product_type or title
                $category = 'Others'; // Default category
                
                if(!empty($row['product_type'])) {
                    // Use product_type if available
                    $category = $row['product_type'];
                } else {
                    // Parse from title if product_type is empty
                    $title = $row['title'];
                    if(stripos($title, 'Road Bike') !== false) {
                        $category = 'Road Bike';
                    } elseif(stripos($title, 'Cruiser') !== false) {
                        $category = 'Cruiser';
                    } elseif(stripos($title, 'Fixed Gear') !== false) {
                        $category = 'Fixed Gear Bike';
                    } elseif(stripos($title, 'Mountain Bike') !== false) {
                        $category = 'Mountain Bike';
                    } elseif(stripos($title, 'BMX') !== false) {
                        $category = 'BMX';
                    } elseif(stripos($title, 'Touring Bike') !== false) {
                        $category = 'Touring Bike';
                    } elseif(stripos($title, 'Folding Bike') !== false) {
                        $category = 'Folding Bike';
                    } elseif(stripos($title, 'Electrical Bike') !== false || stripos($title, 'Electric Bike') !== false) {
                        $category = 'Electrical Bike';
                    } elseif(stripos($title, 'Cyclocross') !== false) {
                        $category = 'Cyclocross Bike';
                    } elseif(stripos($title, 'Hybrid Bike') !== false) {
                        $category = 'Hybrid Bike';
                    }
                }
                
                // Initialize category if not exists
                if(!isset($grouped_products[$category])) {
                    $grouped_products[$category] = array();
                }
                
                $grouped_products[$category][] = $row;
            }
        }
    }
    
    $response['success'] = true;
    $response['data'] = $grouped_products;
} else {
    $response['success'] = false;
    $response['message'] = 'Only GET/POST method allowed';
}

echo json_encode($response);
$conn->close();
?>