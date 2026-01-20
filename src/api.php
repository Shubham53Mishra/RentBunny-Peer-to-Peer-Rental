<?php
header('Content-Type: application/json');
require_once('../config/database.php');

$response = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Login API
    if($action == 'login') {
        if(isset($_POST['email']) && isset($_POST['password'])) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = $_POST['password'];
            
            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = $conn->query($sql);
            
            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if(password_verify($password, $user['password'])) {
                    $response['status'] = true;
                    $response['message'] = 'Login successful';
                    $response['data'] = array(
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    );
                } else {
                    $response['status'] = false;
                    $response['message'] = 'Invalid password';
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'User not found';
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Email and password required';
        }
    }
    
    // Register API
    else if($action == 'register') {
        if(isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = $_POST['password'];
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            if($password != $confirm_password) {
                $response['status'] = false;
                $response['message'] = 'Passwords do not match';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed_password')";
                
                if($conn->query($sql) === TRUE) {
                    $response['status'] = true;
                    $response['message'] = 'Registration successful';
                    $response['data'] = array(
                        'email' => $email,
                        'name' => $name
                    );
                } else {
                    $response['status'] = false;
                    $response['message'] = 'Registration failed: ' . $conn->error;
                }
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Name, email, and password required';
        }
    }
    else {
        $response['status'] = false;
        $response['message'] = 'Action not specified';
    }
} else {
    $response['status'] = false;
    $response['message'] = 'Only POST method allowed';
}

echo json_encode($response);
$conn->close();
?>
