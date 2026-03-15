<?php
require_once 'config/config.php';
require_once 'models/Admin.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $admin = new Admin(getDBConnection());
        if ($admin->login($username, $password)) {
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password';
        }
    }
}

include 'views/login.php';
?>
