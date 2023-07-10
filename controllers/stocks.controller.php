<?php
require_once 'classes/Database.php';
$database = new Database();
$stocks = $database->FetchAll('blood');
$totalStocks = 0;
$bloodrecieved = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);

foreach ($stocks as $blood) {
    if ($blood['type'] == 'A') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['a+'] += $blood['bags'];
        } else {
            $bloodrecieved['a-'] += $blood['bags'];
        }
    } else if ($blood['type'] == 'B') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['b+'] += $blood['bags'];
        } else {
            $bloodrecieved['b-'] += $blood['bags'];
        }
    } else if ($blood['type'] == 'O') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['o+'] += $blood['bags'];
        } else {
            $bloodrecieved['o-'] += $blood['bags'];
        }
    } else if ($blood['type'] == 'AB') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['ab+'] += $blood['bags'];
        } else {
            $bloodrecieved['ab-'] += $blood['bags'];
        }
    }
    $totalStocks += $totalStocks;
}
$today = date("D M j  Y");
require 'view/stocks.view.php';
?>