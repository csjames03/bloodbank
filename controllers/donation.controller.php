<?php
require_once 'classes/Database.php';


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


$donrs = $database->getDonors();
$rrqs = $database->getDonors();
foreach ($donrs as $donrs) {
    $totalStocks += $donrs['donated_bag'];
    if ($donrs['type'] == 'A') {
        if ($donrs['rh'] == '+') {
            $bloodstocks['a+'] += $donrs['donated_bag'];
        } else {
            $bloodstocks['a-'] += $donrs['donated_bag'];
        }
    } else if ($donrs['type'] == 'B') {
        if ($donrs['rh'] == '+') {
            $bloodstocks['b+'] += $donrs['donated_bag'];
        } else {
            $bloodstocks['b-'] += $donrs['donated_bag'];
        }
    } else if ($donrs['type'] == 'O') {
        if ($donrs['rh'] == '+') {
            $bloodstocks['o+'] += $donrs['donated_bag'];
        } else {
            $bloodstocks['o-'] += $donrs['donated_bag'];
        }
    } else if ($donrs['type'] == 'AB') {
        if ($donrs['rh'] == '+') {
            $bloodstocks['ab+'] += $donrs['donated_bag'];
        } else {
            $bloodstocks['ab-'] += $donrs['donated_bag'];
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
        $rrqs = $database->getRecentDonorsSpecificTypes($type['type'], $type['rh']);
    }

}

if (isset($_POST['bloodrequest'])) {
    $doctor = 'Dr. ' . $_POST['doctorname'];
    $bloodtype = $_POST['bloodtype'];
    $bloodrh = $_POST['bloodrh'];
    $bloodquantity = $_POST['bloodquantity'];
    $hospitalid = $_POST['hospital'];
    $bloodcount = 0;
    $s = $database->getRecentDonorsSpecificTypes($type['type'], $type['rh']);
    $stock = array('a+' => 0, 'a-' => 0, 'b+' => 0, 'b-' => 0, 'ab+' => 0, 'ab-' => 0, 'o+' => 0, 'o-' => 0);
    $foundSuitableDonation = false;

    foreach ($s as $blood) {
        $bloodCount = $blood['donated_bag'];

        if ($bloodCount > intval($bloodquantity)) {
            $database->RequestBlood($hospitalid, $doctor, $bloodtype, $bloodrh, $bloodquantity);
            $foundSuitableDonation = true;
            break;
        }
    }

    if (!$foundSuitableDonation) {
        // Handle the case when no suitable blood donation is found
        // You can display an error message or perform any other necessary actions
    }

}

if (isset($_POST['reqid'])) {
    $database->updateStatusToInProgress($_POST['reqid']);
}

if (isset($_POST['delid'])) {
    $database->cancelRequest($_POST['delid']);
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

    // Call the function to add the donation request and check the return value
    $isAdded = $database->addDonationRequest($fname, $mname, $lname, $sex, $type, $rh, $contact, $age, $address, $donatedBag, $medicalStatus);

    if ($isAdded) {
        echo '
        <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">Thank you for Donating!</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
            </div>
        ';
        header("refresh:.5;url=http://localhost/bloodbank/donations");
    } else {
        // Handle the case when the donation request could not be added
        echo "
            <script>
            alert('Error adding donation request');
            </script>
        ";
    }
}

if (isset($_POST['editsave'])) {
    $fname = $_POST['fname'];
    $donorid = $_POST['donor_id'];
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
    // Call the function to add the donation request and check the return value
    $isAdded = $database->updateDonationRequest($donorid, $fname, $mname, $lname, $sex, $type, $rh, $contact, $age, $address, $donatedBag, $medicalStatus);

    if ($isAdded) {
        echo '
        <div class="bg-green-100 fixed top-0 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">Edited Succesfully!</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
            </div>
        ';
        header("refresh:.5;url=http://localhost/bloodbank/donations");
    } else {
        // Handle the case when the donation request could not be added
        echo "
            <script>
            alert('Error adding donation request');
            </script>
        ";
    }


}


/*
    <!-- Modal Start -->
    <div class="fixed bg-rose-300 right-40  z-auto bottom-40 h-fit w-56 px-4 py-2 self-items-center flex justify-self-center self-center hidden"
        id="editform-modals" style="width:50rem;">
        <form action="/bloodbank/donations" method="post"
            class="w-full h-full flex flex-col justify-center items-center">
            <p class="text-2xl font-semibold text-rose-600 my-4"> Donation Blood Form</p>
            <div class="w-full h-fit flex">
                <input type="text" name="fname" class="w-1/2 mx-4 p-3" placeholder="Firstname" value="">
                <input type="text" name="mname" class="w-1/2 mx-4 p-3" placeholder="MiddleName" value="">
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="text" name="lname" class="w-1/2 mx-4 p-3" placeholder="Lastname" value="">
                <select class="w-1/4 mx-4 p-3" name="sex" value="">
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                </select>
                <select class="w-2/12 mx-4 p-3" name="type" value="">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="O">O</option>
                    <option value="AB">AB</option>
                </select>
                <select class="w-2/12 mx-4 p-3" name="rh" value="">
                    <option value="+">+</option>
                    <option value="-">-</option>
                </select>
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="number" name="contact" class="w-1/4 mx-4 p-3" placeholder="Contact" value="">
                <input type="number" name="Age" class="w-1/4 mx-4 p-3" placeholder="Age" value="">
                <input type="text" name="address" class="w-1/2 mx-4 p-3" placeholder="Address" value="">
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="number" name="donated_bag" class="w-1/4 mx-4 p-3" placeholder="Donated Bag" value="">
                <input type="text" name="medical_status" class="w-3/4 mx-4 p-3" placeholder="Medical Status" value="">
            </div>
            <div class="w-full h-fit px-4 py-2 flex justify-center mt-10">
                <button type="submit" name="edit"
                    class="mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">Donate</button>
                <div onclick="editmodalopen()"
                    class="cursor-pointer mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">
                    Cancel
                </div>
            </div>
        </form>

    </div>

    <!-- Modal End -->

    */

if (isset($_POST['search'])) {
    $rrqs = $database->getNamesStartingWith($_POST['searchname']);

}

require 'view/donation.view.php';
?>