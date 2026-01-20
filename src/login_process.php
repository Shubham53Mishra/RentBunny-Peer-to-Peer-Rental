<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            header('Location: ../public/index.php?page=dashboard');
        } else {
            $_SESSION['error'] = 'Invalid password';
            header('Location: ../public/index.php?page=login');
        }
    } else {
        $_SESSION['error'] = 'User not found';
        header('Location: ../public/index.php?page=login');
    }
}
?>
