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
    
    // Check if clothing_adds table exists
    $check_table_sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'clothing_adds' LIMIT 1";
    $table_exists = $conn->query($check_table_sql);
    
    $grouped_products = array(
        'Designer Lehenga' => array(),
        'Bridal Lehenga' => array(),
        'Gown' => array(),
        'Sherwani' => array(),
        'Suit' => array(),
        'Shoes' => array(),
        'Others' => array()
    );
    
    if($table_exists && $table_exists->num_rows > 0) {
        $sql = "SELECT * FROM clothing_adds ORDER BY created_at DESC";
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
                    if(stripos($title, 'Designer Lehenga') !== false) {
                        $category = 'Designer Lehenga';
                    } elseif(stripos($title, 'Bridal Lehenga') !== false) {
                        $category = 'Bridal Lehenga';
                    } elseif(stripos($title, 'Gown') !== false) {
                        $category = 'Gown';
                    } elseif(stripos($title, 'Sherwani') !== false) {
                        $category = 'Sherwani';
                    } elseif(stripos($title, 'Suit') !== false) {
                        $category = 'Suit';
                    } elseif(stripos($title, 'Shoes') !== false) {
                        $category = 'Shoes';
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