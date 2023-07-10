<?php
require_once 'classes/Database.php';



const MAXrequest_size = 20;

$database = new Database();
$stocks = $database->getAllStocks();
$totalStocks = 0;
$bloodstocks = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
$bloodTypes = array(
    'a+' => array('type' => 'A', 'rh' => '+'),
    'a-' => array('type' => 'A', 'rh' => '-'),
    'b+' => array('type' => 'B', 'rh' => '+'),
    'b-' => array('type' => 'B', 'rh' => '-'),
    'ab+' => array('type' => 'AB', 'rh' => '+'),
    'ab-' => array('type' => 'AB', 'rh' => '-'),
    'o+' => array('type' => 'O', 'rh' => '+'),
    'o-' => array('type' => 'O', 'rh' => '-')
);



$hospitals = $database->FetchAll('hospital');


$selectbloodtypes = array(
    'a+' => array('type' => 'A', 'rh' => '+'),
    'b+' => array('type' => 'B', 'rh' => '+'),
    'ab+' => array('type' => 'AB', 'rh' => '+'),
    'o+' => array('type' => 'O', 'rh' => '+'),
);


$reqs = $database->getAllRequests();
$reqss = $database->getAllRequests();

foreach ($reqs as $reqs) {
    $totalStocks += $reqs['requested_bag'];
    if ($reqs['type'] == 'A') {
        if ($reqs['rh'] == '+') {
            $bloodstocks['a+'] += $reqs['requested_bag'];
        } else {
            $bloodstocks['a-'] += $reqs['requested_bag'];
        }
    } else if ($reqs['type'] == 'B') {
        if ($reqs['rh'] == '+') {
            $bloodstocks['b+'] += $reqs['requested_bag'];
        } else {
            $bloodstocks['b-'] += $reqs['requested_bag'];
        }
    } else if ($reqs['type'] == 'O') {
        if ($reqs['rh'] == '+') {
            $bloodstocks['o+'] += $reqs['requested_bag'];
        } else {
            $bloodstocks['o-'] += $reqs['requested_bag'];
        }
    } else if ($reqs['type'] == 'AB') {
        if ($reqs['rh'] == '+') {
            $bloodstocks['ab+'] += $reqs['requested_bag'];
        } else {
            $bloodstocks['ab-'] += $reqs['requested_bag'];
        }
    }
}
$today = date('Y-m-d H:i:s');

$displaytype;
foreach ($bloodTypes as $type) {
    $bt = $type['type'] . $type['rh'];
    if (isset($_POST[$bt])) {
        $displaytype = $_POST[$bt];
        $displaytype = $bt;
        $reqss = $database->getRecentRequestSpecificTypes($type['type'], $type['rh']);
    }

}

if (isset($_POST['bloodrequest'])) {
    $doctor = 'Dr. ' . $_POST['doctorname'];
    $bloodtype = $_POST['bloodtype'];
    $bloodrh = $_POST['bloodrh'];
    $bloodquantity = $_POST['bloodquantity'];
    $hospitalid = $_POST['hospital'];
    $bloodcount = 0;
    $s = $database->getSpecificBlood($bloodtype, $bloodrh);
    $stock = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
    $req = false;
    $bloodCount = $s['bags'];

    if (intval($bloodCount) >= intval($bloodquantity)) {
        $req = $database->RequestBlood($hospitalid, $doctor, $bloodtype, $bloodrh, $bloodquantity);
        $foundSuitableDonation = true;
    } else {
        echo '
            <div class="bg-red-100 fixed top-0 border border-green-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">Blood Stocks Unsufficient!</span>
               
                </div>
            ';
        header("refresh:.2;url=http://localhost/bloodbank/requests");
    }


    if ($req) {
        echo '
    <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">Requested Succesfully!</span>
        
        </div>
    ';
        header("refresh:.3;url=http://localhost/bloodbank/requests");
    }

}

if (isset($_POST['appovedform'])) {
    $database->updateStatusToInProgress($_POST['reqid']);
    $updated = $database->bagsUpdate($_POST['blood_id'], $_POST['requested_bag']);

    echo '
    <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">This request is approved!</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
        </div>
    ';
    header("refresh:.3;url=http://localhost/bloodbank/requests");

}

if (isset($_POST['completeform'])) {
    $database->updateStatusToComplete($_POST['reqid']);

    echo '
    <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">This request is completed!</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
        </div>
    ';
    header("refresh:.3;url=http://localhost/bloodbank/requests");

}

if (isset($_POST['delid'])) {
    $database->cancelRequest($_POST['delid']);
    echo '
    <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">Cancelled Successfully!</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
        </div>
    ';
    header("refresh:.3;url=http://localhost/bloodbank/requests");
}

if (isset($_POST['search'])) {
    $srch = 'Dr. ' . $_POST['searchname'];
    $j = $database->getRequestsByDoctorPrefix($srch);
    $r = $database->getRequestsByHospitalPrefix($_POST['searchname']);
    if (count($j) > 0) {
        $reqss = $j;
    } else {
        $reqss = $r;
    }
}

require 'view/requests.view.php';
?>