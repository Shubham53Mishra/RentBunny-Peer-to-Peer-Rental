<?php
header('Content-Type: application/json');
require_once('../config/database.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Check if user exists
    if($action == 'check_user') {
        if(isset($_GET['email'])) {
            $email = mysqli_real_escape_string($conn, $_GET['email']);
            
            $sql = "SELECT id, name, email FROM users WHERE email = '$email'";
            $result = $conn->query($sql);
            
            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $response['status'] = true;
                $response['message'] = 'User exists';
                $response['data'] = $user;
            } else {
                $response['status'] = false;
                $response['message'] = 'User not found';
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Email parameter required';
        }
    }
    
    // Get all users
    else if($action == 'get_users') {
        $sql = "SELECT id, name, email, created_at FROM users";
        $result = $conn->query($sql);
        
        if($result->num_rows > 0) {
            $users = array();
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $response['status'] = true;
            $response['message'] = 'Users retrieved';
            $response['data'] = $users;
            $response['total'] = count($users);
        } else {
            $response['status'] = false;
            $response['message'] = 'No users found';
        }
    }
    
    else {
        $response['status'] = false;
        $response['message'] = 'Action not specified';
    }
} else {
    $response['status'] = false;
    $response['message'] = 'Only GET method allowed';
}

echo json_encode($response);
$conn->close();
?>
