<?php
session_start();
$url = parse_url($_SERVER['REQUEST_URI'])['path'];

if ($url === '/bloodbank/') {
    require 'controllers/main.controller.php';
} else if ($url === '/bloodbank/logout') {
    require 'partials/logout.php';
} else if ($url === '/bloodbank/adminlogin') {
    require 'controllers/adminlogin.controller.php';
} else if ($url === '/bloodbank/requests') {
    require 'controllers/requests.controller.php';
} else if ($url === '/bloodbank/donations') {
    require 'controllers/donation.controller.php';
} else if ($url === '/bloodbank/stocks') {
    require 'controllers/stocks.controller.php';
} else {
    require 'view/404.php';
}