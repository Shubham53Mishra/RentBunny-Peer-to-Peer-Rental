<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password != $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: ../public/index.php?page=register');
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, email, password, created_at) VALUES ('$name', '$email', '$hashed_password', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = 'Registration successful. Please login.';
        header('Location: ../public/index.php?page=login');
    } else {
        $_SESSION['error'] = 'Registration failed: ' . $conn->error;
        header('Location: ../public/index.php?page=register');
    }
}
?>
