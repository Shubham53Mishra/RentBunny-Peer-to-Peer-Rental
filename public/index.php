<?php
session_start();
require_once('../config/database.php');

// Simple routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch($page) {
    case 'home':
        include('../views/home.php');
        break;
    case 'login':
        include('../views/login.php');
        break;
    case 'register':
        include('../views/register.php');
        break;
    default:
        include('../views/home.php');
}
?>
