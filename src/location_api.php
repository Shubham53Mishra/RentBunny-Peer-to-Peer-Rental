<?php
session_start();
header('Content-Type: application/json');
require_once('../common/db.php');


$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get token from header
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? trim($headers['Authorization']) : '';
    
    // Remove "Bearer " prefix if present
    if(substr($token, 0, 7) == 'Bearer ') {
        $token = substr($token, 7);
    }
    
    if(empty($token)) {
        $response['status'] = false;
        $response['message'] = 'Token required in header';
        echo json_encode($response);
        exit;
    }
    
    // Validate token from database
    $token = mysqli_real_escape_string($conn, $token);
    $sql = "SELECT id, mobile FROM users WHERE token = '$token'";
    $result = $conn->query($sql);
    
    if($result->num_rows == 0) {
        $response['status'] = false;
        $response['message'] = 'Invalid or expired token';
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $mobile = $user['mobile'];
    
    // Get POST data
    $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : 
            (isset($_POST['City']) ? mysqli_real_escape_string($conn, trim($_POST['City'])) : '');
    
    $latitude = isset($_POST['latitude']) ? floatval(trim($_POST['latitude'])) : 
                (isset($_POST['Latitude']) ? floatval(trim($_POST['Latitude'])) : null);
    
    $longitude = isset($_POST['longitude']) ? floatval(trim($_POST['longitude'])) : 
                 (isset($_POST['Longitude']) ? floatval(trim($_POST['Longitude'])) : null);
    
    // DEBUG: Log received data
    error_log('Location API Debug - POST data: ' . json_encode($_POST));
    error_log('Location API Debug - City: ' . $city . ', Latitude: ' . $latitude . ', Longitude: ' . $longitude);
    
    // Validate required fields
    if(empty($city)) {
        $response['status'] = false;
        $response['message'] = 'City is required';
        $response['debug'] = array(
            'received_post' => $_POST,
            'city_value' => $city,
            'city_isset' => isset($_POST['city'])
        );
        echo json_encode($response);
        exit;
    }
    
    if($latitude === null || $longitude === null) {
        $response['status'] = false;
        $response['message'] = 'Latitude and longitude are required';
        $response['debug'] = array(
            'latitude_value' => $latitude,
            'longitude_value' => $longitude,
            'latitude_isset' => isset($_POST['latitude']),
            'longitude_isset' => isset($_POST['longitude'])
        );
        echo json_encode($response);
        exit;
    }
    
    // Validate latitude and longitude ranges
    if($latitude < -90 || $latitude > 90) {
        $response['status'] = false;
        $response['message'] = 'Invalid latitude (must be between -90 and 90)';
        $response['debug'] = array(
            'latitude_value' => $latitude
        );
        echo json_encode($response);
        exit;
    }
    
    if($longitude < -180 || $longitude > 180) {
        $response['status'] = false;
        $response['message'] = 'Invalid longitude (must be between -180 and 180)';
        $response['debug'] = array(
            'longitude_value' => $longitude
        );
        echo json_encode($response);
        exit;
    }
    
    // Update user location
    try {
        $update_sql = "UPDATE users SET city = '$city', latitude = $latitude, longitude = $longitude, updated_at = NOW() WHERE id = $user_id";
        
        if($conn->query($update_sql)) {
            $response['status'] = true;
            $response['message'] = 'Location updated successfully';
            $response['data'] = array(
                'user_id' => $user_id,
                'mobile' => $mobile,
                'city' => $city,
                'latitude' => $latitude,
                'longitude' => $longitude
            );
        } else {
            $response['status'] = false;
            $response['message'] = 'Failed to update location: ' . $conn->error;
            $response['error'] = $conn->error;
            $response['debug'] = array(
                'sql_query' => $update_sql,
                'user_id' => $user_id
            );
        }
    } catch(Exception $e) {
        $response['status'] = false;
        $response['message'] = 'Database error occurred';
        $response['error'] = $e->getMessage();
        $response['debug'] = array(
            'exception' => get_class($e),
            'sql_query' => $update_sql
        );
    }
    
} else {
    $response['status'] = false;
    $response['message'] = 'Only POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
