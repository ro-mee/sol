<?php
require_once 'config/config.php';
require_once 'models/Admin.php';

$admin = new Admin(getDBConnection());
$admin->logout();
redirect('login.php');
?>
