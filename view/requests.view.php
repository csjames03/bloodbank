<?php


require 'partials/head.php';

require_once 'controllers/requests.controller.php';
require 'partials/nav.php';
?>

<main class="fixed overflow-hidden md:ml-72 md:p-16 py-16 flex flex-col h-fit w-fit" style="width:85rem;">
    <!-- Modal Start -->
    <div class="fixed bg-blue-300 z-40 h-fit w-56 px-4 py-2 self-items-center flex justify-self-center self-center  hidden"
        id="form-modal" style="width:50rem;">
        <form action="/bloodbank/requests" method="post"
            class="w-full h-full flex flex-col justify-center items-center">
            <p class="text-2xl font-semibold text-blue-600 my-4"> Request Blood Form</p>
            <label for="hospital" class="block mb-2 text-sm font-medium text-blue-900 text-white">Hospital</label>
            <select id="hospital" name="hospital"
                class="bg-blue-50 border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6  p-2.5 bg-blue-700 border-blue-600 placeholder-blue-400 text-white focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($hospitals as $hospital): ?>
                    <option value="<?php echo $hospital['hospital_id']; ?>">
                        <?php echo $hospital['name']; ?>
                    </option>

                <?php endforeach; ?>
            </select>
            <input type="text" id="first_name" name="doctorname"
                class="bg-gray-50 border border-gray-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6 p-2.5 mt-4"
                placeholder="Doctor's Name" required>
            <div class="grid grid-cols-3 gap-10">
                <div class="col-span-1">
                    <label for="bloodtype" class="mt-4 block mb-2 text-sm font-medium text-blue-900 text-white">Blood
                        Type</label>
                    <select id="bloodtype" name="bloodtype"
                        class="text-blue-800 w-full bg-blue-50 border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6  p-2.5 bg-blue-700 border-blue-600 placeholder-blue-400 text-white focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($selectbloodtypes as $type): ?>

                            <option value="<?php echo $type['type'] ?>">
                                <?php echo $type['type'] ?>
                            </option>

                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="bloodrh" class="mt-4 block mb-2 text-sm font-medium text-blue-900 text-white">Blood
                        Rh</label>
                    <select id="bloodrh" name="bloodrh"
                        class="text-blue-800 w-full bg-blue-50 border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6  p-2.5 bg-blue-700 border-blue-600 placeholder-blue-400 text-white focus:ring-blue-500 focus:border-blue-500">
                        <option value="+" selected>+</option>
                        <option value="-">-</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="bloodquantity"
                        class="mt-4 block mb-2 text-sm font-medium text-blue-900 text-white">Blood
                        Quantity</label>
                    <select id="bloodquantity" name="bloodquantity"
                        class="text-blue-800 w-full bg-blue-50 border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6  p-2.5 bg-blue-700 border-blue-600 placeholder-blue-400 text-white focus:ring-blue-500 focus:border-blue-500">
                        <?php
                        $i = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
                        foreach ($i as $i): ?>
                            <option value="<?php echo $i ?>" selected>
                                <?php echo $i;
                                echo ($i > 1) ? ' bags' : ' bag'; ?>
                            </option>

                        <?php endforeach ?>
                    </select>
                </div>

            </div>
            <div class="w-full h-fit px-4 py-2 flex justify-center mt-10">
                <button type="submit" name="bloodrequest"
                    class="mx-4 text-base px-10 py-3 bg-blue-700 rounded-md text-white font-semibold hover:bg-blue-600 duration-500">Request</button>
                <div onclick="modal()"
                    class="cursor-pointer mx-4 text-base px-10 py-3 bg-rose-700 rounded-md text-white font-semibold hover:bg-rose-600 duration-500">
                    Cancel
                </div>
            </div>
        </form>
    </div>

    <!-- Modal End -->
    <div class="flex">
        <p class="mx-4 my-3 font-bold text-2xl">Request</p>
        <form action="/bloodbank/requests" method="post">
            <div class="h-12 p-2 z-90 w-96 searchbar bg-slate-300 rounded-3xl flex mx-36 cursor-pointer">
                <img src="img/searchicon.png" alt="" class="bg-slate-300 h-5 flex self-center ml-4">
                <input type="text" name="searchname" class="w-full h-full bg-slate-300 outline-slate-300"
                    placeholder="Search Doctor's names">
                <input type="submit" class="hidden" name="search">
            </div>
        </form>
    </div>
    <div class="w-full p-2 grid grid-cols-5 gap-2">

        <div
            class="col-span-3 bg-blue-200 w-full h-32 rounded-2xl shadow-blue-200 shadow-lg hover:bg-blue-300 cursor-pointer hover:shadow-blue-300 hover:shadow-2xl duration-500">
            <div class="w-full flex items-center justify-between">
                <p class="text-blue-600 font-semibold text-xl ml-4 mt-1">Recieved</p>
                <p class="text-blue-600 font font-semibold text-sm mr-32 lg:mt-1">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full flex items-center justify-between">
                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                    <p class="text-base text-blue-600 font-medium">Total</p>
                    <p class="text-3xl text-blue-600 font-bold">
                        <?php echo $totalStocks; ?>
                    </p>
                </div>
                <div class="w-10/12 md:w-10/12 grid md:gap-1 grid-cols-10 md:mr-32 mb-7 items-center">
                    <div class="flex mt-1 ml-4">
                        <div class="flex flex-col mx-1 ">
                            <div
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">A+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">A-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">B+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">B-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">O+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">O-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">AB+</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
                                class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                <p class="text-white text-sm font-medium">AB-</p>
                            </div>
                            <div
                                class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                <p class="text-blue-400 text-sm font-medium mt-3">
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
            <div onclick="modal()"
                class="flex flex-col justify-center items-center w-full h-full p-2 bg-blue-200 rounded-2xl shadow-blue-200 shadow-lg hover:bg-blue-300 cursor-pointer hover:shadow-blue-300 hover:shadow-2xl duration-500">
                <div class="w-10 h-10 rounded-full bg-blue-600 p-2 flex flex-col justify-center">
                    <img src="img/edit-two.png" alt="">
                </div>
                <p class="text-lg font-semibold">Request Form</p>
                <p class="text-xs text-slate-400">Input fill-upped form</p>
            </div>
        </div>
        <div class="col-span-1  px-10">
            <div
                class="w-full hidden flex flex-col justify-center items-center h-full p-2 bg-blue-200 rounded-2xl shadow-blue-200 shadow-lg hover:bg-blue-300 cursor-pointer hover:shadow-blue-300 hover:shadow-2xl duration-500">
                <div class="w-10 h-10 rounded-full bg-blue-600 p-2 flex flex-col justify-center">
                    <img src="img/align-text-bottom.png" alt="">
                </div>
                <p class="text-lg font-semibold">Printable Form</p>
                <p class="text-xs text-slate-400 text-center">Downloadable Donor’s Information Form</p>
            </div>

        </div>
    </div>
    <div class="w-full p-4 overflow-hidden<?php echo ($url === '/bloodbank/requests') ? ('') : ('hidden') ?>"
        style="height: 520px;">
        <a href="/bloodbank/requests" class="font-semibold text-2xl">Types</a>
        <div class="w-full h-full pb-20 grid grid-cols-5 ">
            <div class="col-span-1 p-2 flex flex-col justify-center overflow-x-hidden relative" style="height:470px;">
                <form action="requests" method="post" class="h-full w-full absolute overflow-y-auto">
                    <?php foreach ($bloodTypes as $bloodType): ?>
                        <button type="submit" name="<?php echo $bloodType['type'] . $bloodType['rh']; ?>"
                            class="<?php if ($displaytype === $bloodType['type'] . $bloodType['rh'])
                                echo 'bg-blue-100 ' ?>cursor-pointer mt-2 px-5 py-10 w-full rounded-2xl shadow-lg hover:shadow-2xl flex flex-col items-center justify-center">
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
                <div class="w-full h-full bg-blue-100 rounded-2xl  p-6 relative">
                    <div class="w-full overflow-y-auto pt-4 overflow-y-auto pt-20 grid grid-cols-2"
                        style="height:370px;">
                        <?php
                        if (count($reqss) <= 0)
                            echo '<div class="width-full p-4 text-2xl font-semibold flex justify-center items-center">
                            No request  found :(    
                        </div>
                        ';
                        foreach ($reqss as $donor):
                            if ($donor['progress'] === 'Cancel') {
                            } else { ?>
                                <div onclick="span(<?php echo $donor['request_id']; ?>);"
                                    class="col-span-1 duration-500 ease mx-2 my-2 p-2 h-fit rounded-md bg-white flex items-center justify-between shadow-lg hover:shadow-2xl cursor-pointer duration-500">
                                    <div class="w-full h-full">
                                        <div class="flex relative">
                                            <img src="img/Doctor.avif" alt="" class="w-10 h-10 rounded-full mx-2">
                                            <div class="flex flex-col">
                                                <p class="text-sm font-semibold truncate flex">
                                                    <?php echo $donor['doctor'] . '   (<span class="font-semibold text-green-600">' . $donor['progress'] . '</span> )'; ?>
                                                <div class="flex">
                                                    <form action="requests" method="post">
                                                        <input type="hidden" " name=" blood_id"
                                                            value="<?php echo $donor['blood_id']; ?>">
                                                        <input type="hidden" name="reqid"
                                                            value="<?php echo $donor['request_id']; ?>">
                                                        <input type="hidden" name="requested_bag"
                                                            value="<?php echo $donor['requested_bag']; ?>">
                                                        <?php
                                                        echo ($donor['progress'] === 'Pending') ? ('<button type="submit" name="appovedform" class="p-1 text-xs bg-green-200 hover:bg-green-500"> Approve </button>') : ('');
                                                        echo ($donor['progress'] === 'In Progress') ? ('<button type="submit" name="completeform" class="p-1 text-xs bg-green-200 hover:bg-green-500"> Complete </button>') : ('');
                                                        ?>
                                                    </form>
                                                    <form action="requests" method="post">
                                                        <input type="hidden" name="delid"
                                                            value="<?php echo $donor['request_id']; ?>">
                                                        <?php
                                                        echo ($donor['progress'] === 'Pending') ? ('<button type="submit" class="p-1 text-xs bg-rose-200"> Cancel </button>') : ('')

                                                            ?>
                                                    </form>
                                                </div>
                                                </p>
                                                <p class="text-xs font-semibold text-slate-400 tracking-tighter">
                                                    <?php echo $donor['requested_at']; ?>
                                                </p>
                                            </div>
                                            <div
                                                class="bg-blue-200 absolute right-0 w-8 h-8 p-1 rounded-md flex justify-center items-center mr-2">
                                                <p class="font-semibold text-sm text-blue-600">
                                                    <?php echo $donor['type'] . $donor['rh']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="w-full h-fit p-2 px-6 hidden" id="<?php echo $donor['request_id']; ?>">
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
                                                        <p class="text-sm font-semibold">
                                                            <?php echo $donor['name']; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="w-full h-fit grid grid-cols-4 gap-2">
                                                <div class="col-span-4 flex justify-center items-center">
                                                    <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                        <p class="text-sm font-semibold text-rose-600">
                                                            <?php echo $donor['requested_bag']; ?>
                                                            <?php
                                                            echo $donor['requested_bag'] > 1 ? ('bags') : ('bag');
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                        endforeach; ?>

                    </div>


                </div>
            </div>
        </div>
    </div>
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
        console.log(document.getElementById('form-modal'))
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

</script>