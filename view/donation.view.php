<?php


require 'partials/head.php';

require_once 'controllers/donation.controller.php';
require 'partials/nav.php';
?>

<main class="fixed overflow-hidden md:ml-72 md:p-16 py-16 flex flex-col h-fit w-fit" style="width:85rem;">
    <div class="flex">
        <p class="mx-4 my-3 font-bold text-2xl">Donations</p>
        <form action="/bloodbank/donations" method="post">
            <div class="h-12 p-2 z-50 w-96 searchbar bg-slate-300 rounded-3xl flex mx-36 cursor-pointer">
                <img src="img/searchicon.png" alt="" class="bg-slate-300 h-5 flex self-center ml-4">
                <input type="text" name="searchname" class="w-full h-full bg-slate-300 outline-slate-300"
                    placeholder="Search Donor's names">
                <input type="submit" class="hidden" name="search">
            </div>
        </form>
    </div>
    <div class="w-full p-2 grid grid-cols-5 gap-2">

        <div
            class="col-span-3 bg-rose-200 w-full h-32 rounded-2xl shadow-rose-200 shadow-lg hover:bg-rose-300 cursor-pointer hover:shadow-rose-300 hover:shadow-2xl duration-500">
            <div class="w-full flex items-center justify-between">
                <p class="text-rose-600 font-semibold text-xl ml-4 mt-1">Recieved</p>
                <p class="text-rose-600 font font-semibold text-sm mr-32 lg:mt-1">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full flex items-center justify-between">
                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                    <p class="text-base text-rose-600 font-medium">Total</p>
                    <p class="text-3xl text-rose-600 font-bold">
                        <?php echo $totalStocks; ?>
                    </p>
                </div>
                <div class="w-10/12 md:w-10/12 grid md:gap-1 grid-cols-10 md:mr-32 mb-7 items-center">
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">A+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['a+'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">A-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['a-'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">B+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['b+'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">B-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['b-'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">O+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['o+'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">O-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['o-'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">AB+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['ab+'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-rose-400 rounded-md z-10 flex justify-center items-center hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">AB-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-rose-400 text-sm font-medium mt-3">
                                    <?php
                                    echo $bloodstocks['ab-'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-1  px-10">
            <div onclick="modalopen()"
                class="flex flex-col justify-center items-center w-full h-full p-2 bg-rose-200 rounded-2xl shadow-rose-200 shadow-lg hover:bg-rose-300 cursor-pointer hover:shadow-rose-300 hover:shadow-2xl duration-500">
                <div class="w-10 h-10 rounded-full bg-rose-600 p-2 flex flex-col justify-center">
                    <img src="img/edit-two.png" alt="">
                </div>
                <p class="text-lg font-semibold">Donate Form</p>
                <p class="text-xs text-slate-400">Input fill-upped form</p>
            </div>
        </div>
        <div class="col-span-1  px-10">
            <div
                class="w-full hidden flex flex-col justify-center items-center h-full p-2 bg-rose-200 rounded-2xl shadow-rose-200 shadow-lg hover:bg-rose-300 cursor-pointer hover:shadow-rose-300 hover:shadow-2xl duration-500">
                <div class="w-10 h-10 rounded-full bg-rose-600 p-2 flex flex-col justify-center">
                    <img src="img/align-text-bottom.png" alt="">
                </div>
                <p class="text-lg font-semibold">Printable Form</p>
                <p class="text-xs text-slate-400 text-center">Downloadable Donor’s Information Form</p>
            </div>

        </div>
    </div>
    <div class="w-full p-4 overflow-hidden" style="height: 520px;">
        <p class="font-semibold text-2xl">Types</p>
        <div class="w-full h-full pz-10 grid grid-cols-5 ">
            <div class="col-span-1 p-2 flex flex-col bg-white justify-center overflow-x-hidden relative"
                style="height:470px;">
                <form action="donations" method="post" class="h-full w-full absolute overflow-y-auto">
                    <?php foreach ($bloodTypes as $bloodType): ?>
                        <button type="submit" name="<?php echo $bloodType['type'] . $bloodType['rh']; ?>"
                            class="<?php if ($displaytype === $bloodType['type'] . $bloodType['rh'])
                                echo 'bg-rose-100 ' ?>cursor-pointer mt-2 px-5 py-10 w-full rounded-2xl shadow-lg hover:shadow-2xl flex flex-col items-center justify-center">
                                <div class="w-14 h-14 rounded-full bg-slate-100 flex justify-center items-center">
                                    <img src="img/vector.png" alt="">
                                </div>
                                <p class="font-thin text-3xl">
                                <?php echo $bloodType['type'] . $bloodType['rh']; ?>
                            </p>
                            <input type="hidden" value="<?php echo $bloodType['type'] . $bloodType['rh'] ?>">
                        </button>
                    <?php endforeach; ?>

                </form>
            </div>
            <div class="col-span-4 p-2">
                <div class="w-full h-full bg-rose-100 rounded-2xl  p-6 relative">
                    <div class="w-full overflow-y-auto pt-4 overflow-y-auto pz-10 grid grid-cols-2"
                        style="height:370px;">
                        <?php
                        if (count($rrqs) <= 0)
                            echo '<div class="width-full p-4 text-2xl font-semibold flex justify-center items-center">
                            No Donors  found :(    
                        </div>
                        ';
                        foreach ($rrqs as $donor):
                            ?>
                            <div>
                                <form action="/bloodbank/donations" method="POST">
                                    <input type="hidden" name="fname" value="<?= $donor['fname'] ?>">
                                    <input type="hidden" name="mname" value="<?= $donor['mname'] ?>">
                                    <input type="hidden" name="lname" value="<?= $donor['lname'] ?>">
                                    <input type="hidden" name="sex" value="<?= $donor['sex'] ?>">
                                    <input type="hidden" name="donor_id" value="<?= $donor['donor_id'] ?>">
                                    <input type="hidden" name="type" value="<?= $donor['type'] ?>">
                                    <input type="hidden" name="rh" value="<?= $donor['rh'] ?>">
                                    <input type="hidden" name="contact" value="<?= $donor['contact'] ?>">
                                    <input type="hidden" name="age" value="<?= $donor['age'] ?>">
                                    <input type="hidden" name="address" value="<?= $donor['address'] ?>">
                                    <input type="hidden" name="donated_bag" value="<?= $donor['donated_bag'] ?>">
                                    <input type="hidden" name="medical_status" value="<?= $donor['medical_status'] ?>">
                                    <button type="submit" name="edits"
                                        class="text-xs p-1 rounded-md bg-green-200 text-green-900 font-semibold h-fit w-fit relative top-4 cursor-pointer hover:bg-green-400">
                                        Edit
                                    </button>
                                </form>
                                <div onclick="span('<?php echo $donor['donation_id']; ?>');"
                                    class="col-span-1 duration-500 ease mx-2 my-2 p-2 h-fit rounded-md bg-white flex items-center justify-between shadow-lg hover:shadow-2xl cursor-pointer duration-500">
                                    <div class="w-full h-full relative flex flex-col">
                                        <div class="flex relative">
                                            <img src="img/<?php echo $donor['sex']; ?>.png" alt=""
                                                class="w-10 h-10 rounded-full mx-2 ">

                                            <div class="flex flex-col">
                                                <p class="text-sm font-semibold truncate text-rose-600 flex">
                                                    <?php echo $donor['fname'] . ' ' . $donor['mname'] . ' ' . $donor['lname']; ?>
                                                </p>
                                                <p class="text-xs font-semibold text-slate-400 tracking-tighter">
                                                    <?php echo $donor['donation_date']; ?>
                                                </p>
                                            </div>
                                            <div
                                                class="bg-rose-200 absolute right-0 w-8 h-8 p-1 rounded-md flex justify-center items-center mr-2">
                                                <p class="font-semibold text-sm text-rose-600">
                                                    <?php echo $donor['type'] . $donor['rh']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="w-full h-fit p-2 px-6 hidden" id="<?php echo $donor['donation_id']; ?>">
                                            <div class="w-full h-fit grid grid-cols-4 gap-2">
                                                <div class="col-span-3 flex justify-center items-center">
                                                    <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                        <p class="text-sm font-semibold">
                                                            <?php echo $donor['address']; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="w-full h-fit grid grid-cols-4 gap-2">
                                                <div class="col-span-4 flex justify-center items-center">
                                                    <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                        <p class="text-sm font-semibold text-rose-600">
                                                            <?php echo $donor['donated_bag']; ?>
                                                            <?php
                                                            echo $donor['donated_bag'] > 1 ? ('bags') : ('bag');
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach; ?>

                    </div>


                </div>
            </div>
        </div>
    </div>
    <?php
    if (isset($_POST['edits'])) {
        $fname = $_POST['fname'];
        $mname = $_POST['mname'];
        $lname = $_POST['lname'];
        $sex = $_POST['sex'];
        $type = $_POST['type'];
        $rh = $_POST['rh'];
        $contact = $_POST['contact'];
        $age = $_POST['age'];
        $address = $_POST['address'];
        $donatedBag = $_POST['donated_bag'];
        $medicalStatus = $_POST['medical_status'];
        $donorid = $_POST['donor_id'];
        ?>
        <div class="fixed bg-rose-300 right-90  z-90 bottom-40 h-fit w-56 px-4 py-2 self-items-center flex justify-self-center self-center"
            id="editform-modals" style="width:50rem;">
            <form action="/bloodbank/donations" method="post"
                class="w-full h-full flex flex-col justify-center items-center">
                <p class="text-2xl font-semibold text-rose-600 my-4"> Donation Blood Form</p>
                <div class="w-full h-fit flex">
                    <input type="hidden" name="donor_id" value="<?php echo $donorid; ?>">
                    <input type="text" name="fname" class="w-1/2 mx-4 p-3" placeholder="Firstname" value="<?= $fname ?>">
                    <input type="text" name="mname" class="w-1/2 mx-4 p-3" placeholder="MiddleName" value="<?= $mname ?>">
                </div>
                <div class="w-full h-fit flex py-1">
                    <input type="text" name="lname" class="w-1/2 mx-4 p-3" placeholder="Lastname" value="<?= $lname ?>">
                    <select class="w-1/4 mx-4 p-3" name="sex">
                        <option value="Female" <?php echo $sex == 'Female' ? ('selected') : ('') ?>>Female</option>
                        <option value="Male" <?php echo $sex == 'Male' ? ('selected') : ('') ?>>Male</option>
                    </select>
                    <select class="w-2/12 mx-4 p-3" name="type" value="<?= $type ?>">
                        <option value="A" <?php echo $type == 'A' ? ('selected') : ('') ?>>A</option>
                        <option value="B" <?php echo $type == 'B' ? ('selected') : ('') ?>>B</option>
                        <option value="O" <?php echo $type == 'O' ? ('selected') : ('') ?>>O</option>
                        <option value="AB" <?php echo $type == 'A' ? ('selected') : ('') ?>>AB</option>
                    </select>
                    <select class="w-2/12 mx-4 p-3" name="rh" value="<?= $rh ?>">
                        <option value="+" <?php echo $rh == '+' ? ('selected') : ('') ?>>+</option>
                        <option value="-" <?php echo $rh == '-' ? ('selected') : ('') ?>>-</option>
                    </select>
                </div>
                <div class="w-full h-fit flex py-1">
                    <input type="number" name="contact" class="w-1/4 mx-4 p-3" placeholder="Contact"
                        value="<?= $contact ?>">
                    <input type="number" name="Age" class="w-1/4 mx-4 p-3" placeholder="Age" value="<?= $age ?>">
                    <input type="text" name="address" class="w-1/2 mx-4 p-3" placeholder="Address" value="<?= $address ?>">
                </div>
                <div class="w-full h-fit flex py-1">
                    <input type="number" name="donated_bag" class="w-1/4 mx-4 p-3" placeholder="Donated Bag"
                        value="<?= $donatedBag ?>">
                    <input type="text" name="medical_status" class="w-3/4 mx-4 p-3" placeholder="Medical Status"
                        value="<?= $medicalStatus ?>">
                </div>
                <div class="w-full h-fit px-4 py-2 flex justify-center mt-10">
                    <button type="submit" name="editsave"
                        class="mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">Donate</button>
                    <button onclick="reload()"
                        class="cursor-pointer mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">
                        Cancel
                    </button>
                </div>
            </form>

        </div>

        <?php
    }
    ?>
    <!-- Modal Start -->
    <div class="fixed bg-rose-300 right-40  z-50 bottom-40 h-fit w-56 px-4 py-2 self-items-center flex justify-self-center self-center hidden"
        id="form-modals" style="width:50rem;">
        <form action="/bloodbank/donations" method="post"
            class="w-full h-full flex flex-col justify-center items-center">
            <p class="text-2xl font-semibold text-rose-600 my-4"> Donation Blood Form</p>
            <div class="w-full h-fit flex">
                <input type="text" name="fname" class="w-1/2 mx-4 p-3" placeholder="Firstname" required>
                <input type="text" name="mname" class="w-1/2 mx-4 p-3" placeholder="MiddleName" required>
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="text" name="lname" class="w-1/2 mx-4 p-3" placeholder="Lastname" required>
                <select class="w-1/4 mx-4 p-3" name="sex" required>
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                </select>
                <select class="w-2/12 mx-4 p-3" name="type" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="O">O</option>
                    <option value="AB">AB</option>
                </select>
                <select class="w-2/12 mx-4 p-3" name="rh" required>
                    <option value="+">+</option>
                    <option value="-">-</option>
                </select>
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="number" name="contact" class="w-1/4 mx-4 p-3" placeholder="Contact" required>
                <input type="number" name="Age" class="w-1/4 mx-4 p-3" placeholder="Age" required>
                <input type="text" name="address" class="w-1/2 mx-4 p-3" placeholder="Address" required>
            </div>
            <div class="w-full h-fit flex py-1">
                <input type="number" name="donated_bag" class="w-1/4 mx-4 p-3" placeholder="Donated Bag" required>
                <input type="text" name="medical_status" class="w-3/4 mx-4 p-3" placeholder="Medical Status" required>
            </div>
            <div class="w-full h-fit px-4 py-2 flex justify-center mt-10">
                <button type="submit" name="blooddonate"
                    class="mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">Donate</button>
                <div onclick="modalopen()"
                    class="cursor-pointer mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">
                    Cancel
                </div>
            </div>
        </form>

    </div>

    <!-- Modal End -->
</main>
<script>
    function span(m) {
        let open = false;
        let classes = document.getElementById(m).classList
        for (let i = 0; i < classes.length; i++) {
            console.log(classes[i])
            if (classes[i] === 'hidden') {
                open = true;
            } else {
                open = false;
            }
        }
        if (open) {
            classes.remove('hidden');
        } else {
            classes.add('hidden');
        }
    }

    function modal() {
        let open;
        let classes = document.getElementById('form-modal').classList
        for (let i = 0; i < classes.length; i++) {
            console.log(classes[i])
            if (classes[i] === 'hidden') {
                open = true;
            } else {
                open = false;
            }
        }
        console.log(open);
        if (open) {
            classes.remove('hidden');
        } else {
            classes.add('hidden');
        }
    }
    function editmodalopen() {
        let open;
        let classes = document.getElementById('editform-modal').classList
        for (let i = 0; i < classes.length; i++) {
            console.log(classes[i])
            if (classes[i] === 'hidden') {
                open = true;
            } else {
                open = false;
            }
        }
        console.log(open);
        if (open) {
            classes.remove('hidden');
        } else {
            classes.add('hidden');
        }
    }

    function modalopen() {
        let open;
        let classes = document.getElementById('form-modals').classList
        for (let i = 0; i < classes.length; i++) {
            console.log(classes[i])
            if (classes[i] === 'hidden') {
                open = true;
            } else {
                open = false;
            }
        }
        console.log(open);
        if (open) {
            classes.remove('hidden');
        } else {
            classes.add('hidden');
        }
    }

    function reload() {
        location.reload();
    }

</script>