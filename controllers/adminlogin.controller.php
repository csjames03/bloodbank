<?php

require 'classes/Database.php';

require 'adminconfig.php';


$database = new Database();

$submmited = false;
$adminNameExist = true;
$passwordCorrect = true;

if (isset($_POST['submit'])) {
    $submmited = true;
    $password = $_POST['password'];
    $name = $_POST['name'];
    if ($name === $admin['admin'] && $password === $admin['password']) {
        $_SESSION['user'] = 'admin';
    }
    $adminNameExist = $admin['admin'] === $name ? (true) : (false);
    $passwordCorrect = $admin['password'] === $password ? (true) : (false);
}



require 'view/adminlogin.view.php';