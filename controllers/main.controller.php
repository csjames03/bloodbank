<?php

require 'classes/Database.php';


$database = new Database();


$allreqs = $database->getAllRequests();

$hospitals = $database->FetchAll("Hospital");
$donors = $database->FetchAll("Donor");
$donates = $database->FetchAll("donates");
$hospitalsnameexist = true;
$hospitalidexist = true;
$submmited = false;
$today = date("D M j  Y");
if (isset($_POST['submit'])) {
    $submmited = true;
    $id = $_POST['hospitalid'];
    $name = $_POST['hosptalname'];
    $doesExist = $database->Authenticate($name, $id);
    $hospitalsnameexist = $database->AuthenticateName($name);
    $hospitalidexist = $doesExist;
    if ($doesExist) {
        $_SESSION['user'] = $name;
        $_SESSION['id'] = $id;
    }
}

$totalRecieved = 0;
$request = $database->getAllCurrentRequests();
$totalRequests = 0;
$stocks = $database->getAllStocks();
$totalStocks = 0;
$totalbloods = $database->getDonors();
$bloodrecieved = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
$bloodrequest = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
$bloodstocks = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
$selectbloodtypes = array(
    'a+' => array('type' => 'A', 'rh' => '+'),
    'b+' => array('type' => 'B', 'rh' => '+'),
    'ab+' => array('type' => 'AB', 'rh' => '+'),
    'o+' => array('type' => 'O', 'rh' => '+'),
);
if (isset($_SESSION['user'])) {
    $hosp = $database->getRequestsByHospital($_SESSION['user']);
}



foreach ($stocks as $blood) {
    if ($blood['type'] == 'A') {
        if ($blood['rh'] == '+') {
            $bloodstocks['a+'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        } else {
            $bloodstocks['a-'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        }
    } else if ($blood['type'] == 'B') {
        if ($blood['rh'] == '+') {
            $bloodstocks['b+'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        } else {
            $bloodstocks['b-'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        }
    } else if ($blood['type'] == 'O') {
        if ($blood['rh'] == '+') {
            $bloodstocks['o+'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        } else {
            $bloodstocks['o-'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        }
    } else if ($blood['type'] == 'AB') {
        if ($blood['rh'] == '+') {
            $bloodstocks['ab+'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        } else {
            $bloodstocks['ab-'] += $blood['bags'];
            $totalStocks += $blood['bags'];
        }
    }
}
foreach ($request as $blood) {
    if ($blood['type'] == 'A') {
        if ($blood['rh'] == '+') {
            $bloodrequest['a+'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        } else {
            $bloodrequest['a-'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        }
    } else if ($blood['type'] == 'B') {
        if ($blood['rh'] == '+') {
            $bloodrequest['b+'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        } else {
            $bloodrequest['b-'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        }
    } else if ($blood['type'] == 'O') {
        if ($blood['rh'] == '+') {
            $bloodrequest['o+'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        } else {
            $bloodrequest['o-'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        }
    } else if ($blood['type'] == 'AB') {
        if ($blood['rh'] == '+') {
            $bloodrequest['ab+'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        } else {
            $bloodrequest['ab-'] += $blood['requested_bag'];
            $totalRequests += $blood['requested_bag'];
        }
    }
}
foreach ($totalbloods as $blood) {
    if ($blood['type'] == 'A') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['a+'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        } else {
            $bloodrecieved['a-'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        }
    } else if ($blood['type'] == 'B') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['b+'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        } else {
            $bloodrecieved['b-'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        }
    } else if ($blood['type'] == 'O') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['o+'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        } else {
            $bloodrecieved['o-'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        }
    } else if ($blood['type'] == 'AB') {
        if ($blood['rh'] == '+') {
            $bloodrecieved['ab+'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        } else {
            $bloodrecieved['ab-'] += $blood['donated_bag'];
            $totalRecieved += $blood['donated_bag'];
        }
    }
}


$recentdonors = $database->getRecentDonors();


$progress = $database->getAllRequests();

if (isset($_POST['submit'])) {

    if (isset($_POST['prog'])) {
        $selectedOption = $_POST['prog'];

        // Handle the selected option based on its value
        if ($selectedOption === 'Completed') {
            var_dump($selectedOption);
        } elseif ($selectedOption === 'Pending') {
            var_dump($selectedOption);
        } elseif ($selectedOption === 'In Progress') {
            var_dump($selectedOption);
        }
        die();
    }
}


if (isset($_POST['blooddonate'])) {
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $sex = $_POST['sex'];
    $type = $_POST['type'];
    $rh = $_POST['rh'];
    $contact = $_POST['contact'];
    $age = $_POST['Age'];
    $address = $_POST['address'];
    $donatedBag = $_POST['donated_bag'];
    $medicalStatus = $_POST['medical_status'];

    // Assuming you have a database connection established
    $db = new Database();

    // Call the function to add the donation request and check the return value
    $isAdded = $db->addDonationRequest($fname, $mname, $lname, $sex, $type, $rh, $contact, $age, $address, $donatedBag, $medicalStatus);

    if ($isAdded) {
        echo '
        <div class="bg-green-100 fixed top-0 border border-green-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Donated Successfully!</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
            </div>
        ';
    } else {
        // Handle the case when the donation request could not be added
        echo "Error adding donation request.";
    }
    header("refresh:.4;url=http://localhost/bloodbank/");
}


if (isset($_POST['bloodrequest'])) {
    $doctor = 'Dr. ' . $_POST['doctorname'];
    $bloodtype = $_POST['bloodtype'];
    $bloodrh = $_POST['bloodrh'];
    $bloodquantity = $_POST['bloodquantity'];
    $hospitalid = $_SESSION['id'];
    $bloodcount = 0;
    $s = $database->getSpecificBlood($bloodtype, $bloodrh);
    $stock = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
    $req = false;
    $bloodCount = $s['bags'];

    if (intval($bloodCount) >= intval($bloodquantity)) {
        $req = $database->RequestBlood($hospitalid, $doctor, $bloodtype, $bloodrh, $bloodquantity);
        $foundSuitableDonation = true;
        echo '
        <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Requested Successfully!</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
            </div>
        ';
    } else {
        echo '
            <div class="bg-red-100 fixed top-0 border border-green-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">Blood Stocks Unsufficient!</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
                </div>
            ';
    }
    header("refresh:.4;url=http://localhost/bloodbank/");

}

if (isset($_POST['selectform'])) {
    $database->updateStatusToInProgress($_POST['reid']);
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
    header("refresh:.4;url=http://localhost/bloodbank/requests");
}




require 'view/main.view.php';