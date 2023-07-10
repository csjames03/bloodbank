<?php
require 'partials/head.php';

require_once 'controllers/main.controller.php';

if (!isset($_SESSION['user'])) { ?>

    <nav class="fixed h-16 p-3 flex">
        <img src="img/blood-logo.png" alt="Blood Bank Logo" class="w-16 animate-pulse">
        <div class="w-full p-3 flex justify-center items-center flex-col">
            <p class="text-2xl font-medium tracking-wider">
                <span class="text-rose-500">B</span>lood <span class="text-rose-500">B</span>ank
            </p>
            <p class="text-sm font-medium tracking-wide">
                <span class="text-rose-500">M</span>anagement <span class="text-rose-500">S</span>ystem
            </p>
        </div>
    </nav>
    <form action="" method="post">
        <div class="w-screen h-screen flex justify-center items-center">
            <div class="w-4/6 h-96 bg-slate-50 shadow-2xl  flex flex-col text-rose-600 items-center py-4 rounded-md">
                <p class="text-2xl font-bold tracking-wide text-rose-600">Hospital's Portal</p>
                <input type="text" name="hosptalname"
                    class="mt-10 text-lg font-semibold focus:outline placeholder:font-normal focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 w-11/12 p-3 mt-4 h-12 rounded-md bg-slate-200 shadow-lg"
                    placeholder="Enter your hospital's name" value="<?php if ($submmited)
                        echo $name ?>">
                    <p id="errorname" class="text-red-500 text-medium font-semibold flex self-start mx-6 <?php if ($hospitalsnameexist)
                        echo 'hidden'; ?>">
                    Incorrect name!
                </p>
                <input type="password" name="hospitalid"
                    class="text-lg font-semibold placeholder:font-normal focus:outline  focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 w-11/12 mt-4 p-3 h-12 rounded-md bg-slate-200 shadow-lg"
                    placeholder="Enter your hospital's id" value="<?php if ($submmited)
                        echo $id ?>">
                    <p id=" errorid" class="text-red-500 text-medium <?php if ($hospitalsnameexist && $hospitalidexist)
                        echo 'hidden'; ?> font-semibold flex self-start mx-6">
                    Incorrect id!
                </p>
                <input type="submit" value="Login" name="submit"
                    class="focus:outline  focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 mt-10 w-56  hover:w-60 p-3 py-4 bg-rose-600 cursor-pointer duration-500 rounded-md shadow-xl hover: hover:shadow-2xl hover:shadow-rose-700 hadow-rose-400 font-semibold text-white tracking-wide">
            </div>

        </div>
    </form>
    <?php
} else {
    require 'partials/nav.php';
    ?>
    <main class="fixed overflow-hidden md:ml-72 md:p-16 py-16 flex flex-col h-fit w-fit ">
        <p class="mx-4 my-3 font-bold text-2xl ">
            Dashboard
        </p>
        <div class="w-full h-full ">
            <div class=" mx-4 p-3 max-h-32 stocks-dashboard-container
        <?php echo $_SESSION['user'] === 'admin' ? ('
        flex
        ') : ('bg-rose-200'); ?>
        
     rounded-2xl">
                <div class="w-full h-full grid grid-cols-3 gap-5 <?php if ($_SESSION['user'] != 'admin')
                    echo 'hidden'; ?>">
                    <a href="/bloodbank/requests"
                        class="bg-blue-200 shadow-blue-200 shadow-lg w-full h-32 rounded-2xl hover:bg-blue-300 hover:shadow-blue-400 hover:shadow-2xl duration-500">
                        <div class="w-full flex items-center relative">
                            <p class=" text-blue-600 font-semibold text-xl ml-4 mt-3 margin">Requests</p>
                            <p class="text-blue-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                                <?php echo $today ?>
                            </p>
                        </div>
                        <div class="w-full h-fit flex ">
                            <div class="w-3/12 mt-5">
                                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                                    <p class="text-sm text-blue-600 font-medium">Total</p>
                                    <p class="text-3xl text-blue-600 font-bold">
                                        <?php echo $totalRequests; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">B+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-blue-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrequest['b+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">O-</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-blue-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrequest['o-'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-blue-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-blue-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">A+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-blue-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrequest['a+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </a>
                    <a href="/bloodbank/donations"
                        class="bg-rose-200 w-full h-32 rounded-2xl shadow-lg shadow-rose-200 hover:bg-rose-300 duration-300 hover:shadow-2xl hover:shadow-rose-300 cursor-pointer">
                        <div class="w-full flex items-center relative">
                            <p class=" text-rose-600 font-semibold text-xl ml-4 mt-3 margin">Recieved</p>
                            <p class="text-rose-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                                <?php echo $today ?>
                            </p>
                        </div>
                        <div class="w-full h-fit flex ">
                            <div class="w-3/12 mt-5">
                                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                                    <p class="text-sm text-rose-600 font-medium">Total</p>
                                    <p class="text-3xl text-rose-600 font-bold">
                                        <?php echo $totalRecieved; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">B+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-rose-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrecieved['b+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">O-</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-rose-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrecieved['o-'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">A+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-rose-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodrecieved['a+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </a>
                    <a href="/bloodbank/stocks"
                        class="bg-green-200 w-full h-32 rounded-2xl shadow-green-200 shadow-lg hover:bg-green-300 cursor-pointer hover:shadow-green-300 hover:shadow-2xl duration-500">
                        <div class="w-full flex items-center relative">
                            <p class=" text-green-600 font-semibold text-xl ml-4 mt-3 margin">In Stock</p>
                            <p class="text-green-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                                <?php echo $today ?>
                            </p>
                        </div>
                        <div class="w-full h-fit flex ">
                            <div class="w-3/12 mt-5">
                                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                                    <p class="text-sm text-green-600 font-medium">Total</p>
                                    <p class="text-3xl text-green-600 font-bold">
                                        <?php echo $totalStocks; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-green-400 rounded-md z-20 flex justify-center items-center cursor  hover:pb-2 hover:bg-green-600 hover:bg-green-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">B+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-green-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodstocks['b+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-green-400 rounded-md z-20 flex justify-center items-center cursor  hover:pb-2 hover:bg-green-600 hover:bg-green-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">O+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-green-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodstocks['o+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-green-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-green-600 cursor-pointer duration-300">
                                        <p class="text-white text-sm font-medium">A+</p>
                                    </div>
                                    <div
                                        class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                                        <p class="text-green-400 text-sm font-medium mt-3">
                                            <?php
                                            echo $bloodstocks['a+'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </a>
                </div>
                <?php if ($_SESSION['user'] === 'admin')
                    echo '</div>'; ?>


                <div class="w-full h-full">
                    <div class="w-full h-full <?php if ($_SESSION['user'] === 'admin')
                        echo 'hidden'; ?>">
                        <div class="w-full flex items-center justify-between">
                            <p class="text-rose-600 font-semibold text-xl ml-4 mt-1">In Stock</p>
                            <p class="text-rose-600 font font-semibold text-sm mr-32 lg:mt-1">
                                <?php echo $today ?>
                            </p>
                        </div>
                        <div class="w-full flex items-center justify-between">
                            <div class="w-3/12">
                                <div class="flex flex-col mt-1 md:mx-4 ml-4">
                                    <p class="text-base text-rose-600 font-medium">Total</p>
                                    <p class="text-3xl text-rose-600 font-bold">
                                        <?php echo $totalStocks; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="w-10/12 md:w-10/12 grid md:gap-1 grid-cols-10 md:mr-32 mb-7 items-center">
                                <div class="flex mt-1 ml-4">
                                    <div class="flex flex-col mx-1 ">
                                        <div
                                            class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                                <div class="flex flex-col mx-1">
                                    <div
                                        class="w-10 h-10 bg-rose-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-rose-600 cursor-pointer duration-300">
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
                        </div>
                    </div>
                </div>
                <div class="  bg-rose-50 rounded-md p-4 mt-5 flex  z-40 fixed w-9/12"
                    style="max-height: 500px; max-width: 1300px; ">
                    <div class="w-8/12 h-full <?php if ($_SESSION['user'] == 'admin')
                        echo 'hidden';
                    ?>">
                        <p class="mx-2 font-medium text-lg tracking-wide absolute top-0 left-0">Delivery Progress:

                        </p>
                        <div class="h-96 w-full mt-10 mb-10  overflow-y-auto">
                            <?php foreach ($hosp as $p): ?>
                                <div class="w-full h-20 p-4 mt-10 bg-slate-500 rounded-2xl my-4 relative">
                                    <div class="w-full items-center flex h-full bg-slate-100 p-2 pr-3 rounded-2xl">
                                        <div class="h-full items-center rounded-2xl flex relative <?php
                                        if ($p['progress'] == 'Pending') {
                                            echo 'w-1/3 bg-rose-600 ';
                                        } else if ($p['progress'] == 'In Progress') {
                                            echo 'w-2/3 bg-rose-600 ';
                                        } else if ($p['progress'] == 'Complete') {
                                            echo 'w-full bg-rose-600 ';
                                        }

                                        if ($p['progress'] == 'Cancel') {
                                            echo 'w-full bg-slate-900';
                                        }
                                        ?>">
                                            <div class="ml-10 flex text-xs font-semibold text-white">Requested by:
                                                <?php echo $p['doctor'] ?>
                                                <?php if ($p['progress'] == 'Cancel') {
                                                    echo '-<p class="text-rose-600">(Request Cancelled)   </p>';
                                                    echo '-';
                                                    echo $p['name'];
                                                } ?>
                                            </div>
                                            <img src="img/truck-kun.png" alt="" class="absolute <?php
                                            if ($p['progress'] == 'Cancel') {
                                                echo ' hidden';
                                            }
                                            ?>" style="right:-20px;">
                                        </div>
                                        <img src="img/hospital.png" alt="" class="mr-4 absolute mt-1 right-0 top-4"
                                            style="right:-2px;">
                                        <img src="img/request.png" alt="" class="mr-4 absolute left-5 top-5">
                                        <p class="ml-10 text-xs font-semibold">
                                            <?php echo $p['name'] ?>

                                        </p>

                                    </div>
                                    <div class="absolute flex bottom-top-36 justify-between w-full">
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class=" <?php if ($_SESSION['user'] == 'admin')
                        echo 'hidden';
                    ?> w-4/12 h-full">
                        <!-- Modal Start -->
                        <div class="fixed bg-rose-300 right-40  z-90 h-fit w-56 px-4 py-2 self-items-center flex justify-self-center self-center  hidden"
                            id="form-modals" style="width:50rem;">
                            <form action="/bloodbank/" method="post"
                                class="w-full h-full flex flex-col justify-center items-center">
                                <p class="text-2xl font-semibold text-rose-600 my-4"> Donation Blood Form</p>
                                <div class="w-full h-fit flex">
                                    <input type="text" name="fname" class="w-1/2 mx-4 p-3" placeholder="Firstname" required>
                                    <input type="text" name="mname" class="w-1/2 mx-4 p-3" placeholder="MiddleName"
                                        required>
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
                                        <option value="A">O</option>
                                        <option value="B">AB</option>
                                    </select>
                                    <select class="w-2/12 mx-4 p-3" name="rh" required>
                                        <option value="+">+</option>
                                        <option value="-">-</option>
                                    </select>
                                </div>
                                <div class="w-full h-fit flex py-1">
                                    <input type="number" name="contact" class="w-1/4 mx-4 p-3" placeholder="Contact"
                                        required>
                                    <input type="number" name="Age" class="w-1/4 mx-4 p-3" placeholder="Age" required>
                                    <input type="text" name="address" class="w-1/2 mx-4 p-3" placeholder="Address" required>
                                </div>
                                <div class="w-full h-fit flex py-1">
                                    <input type="number" name="donated_bag" class="w-1/4 mx-4 p-3" placeholder="Donated Bag"
                                        required>
                                    <input type="text" name="medical_status" class="w-3/4 mx-4 p-3"
                                        placeholder="Medical Status" required>
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
                        <!-- Modal Start -->
                        <div class="fixed bg-blue-300 z-40 h-fit w-56  self-items-center flex justify-self-center self-center right-40  hidden"
                            id="form-modal" style="width:50rem;">
                            <form action="/bloodbank/" method="post"
                                class="w-full h-full flex flex-col justify-center items-center">
                                <p class="text-2xl font-semibold text-blue-600 my-4"> Request Blood Form</p>
                                <label for="hospital"
                                    class="block mb-2 text-sm font-medium text-blue-900 text-white">Hospital</label>
                                <input type="hidden" name="hospital">
                                <input type="text" id="first_name" name="doctorname"
                                    class="bg-gray-50 border border-gray-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/6 p-2.5 mt-4"
                                    placeholder="Doctor's Name" required>
                                <div class="grid grid-cols-3 gap-10">
                                    <div class="col-span-1">
                                        <label for="bloodtype"
                                            class="mt-4 block mb-2 text-sm font-medium text-blue-900 text-white">Blood
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
                                        <label for="bloodrh"
                                            class="mt-4 block mb-2 text-sm font-medium text-blue-900 text-white">Blood
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
                        <div class="p-2 h-fit w-fit flexgrid grid grid-cols-4 ">
                            <div class="col-span-2  px-1">
                                <div onclick="modal()"
                                    class="flex flex-col justify-center items-center w-full h-full p-5 bg-blue-200 rounded-2xl shadow-blue-200 shadow-lg hover:bg-blue-300 cursor-pointer hover:shadow-blue-300 hover:shadow-2xl duration-500">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 p-2 flex flex-col justify-center">
                                        <img src="img/edit-two.png" alt="">
                                    </div>
                                    <p class="text-lg font-semibold">Request Form</p>
                                    <p class="text-xs text-slate-400">Input fill-upped form</p>
                                </div>
                            </div>
                            <div class="col-span-2  px-1">
                                <div
                                    class="w-full hidden flex flex-col justify-center items-center h-full p-2 bg-blue-200 rounded-2xl shadow-blue-200 shadow-lg hover:bg-blue-300 cursor-pointer hover:shadow-blue-300 hover:shadow-2xl duration-500">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 p-2 flex flex-col justify-center">
                                        <img src="img/align-text-bottom.png" alt="">
                                    </div>
                                    <p class="text-lg font-semibold">Printable Form</p>
                                    <p class="text-xs text-slate-400 text-center">Downloadable Donor’s Information Form
                                    </p>
                                </div>

                            </div>
                            <div class="col-span-2  px-1 mt-2">
                                <div onclick="modalopen()"
                                    class="flex flex-col justify-center items-center w-full h-full p-5 bg-teal-200 rounded-2xl shadow-teal-200 shadow-lg hover:bg-teal-300 cursor-pointer hover:shadow-teal-300 hover:shadow-2xl duration-500">
                                    <div class="w-10 h-10 rounded-full bg-teal-600 p-2 flex flex-col justify-center">
                                        <img src="img/edit-two.png" alt="">
                                    </div>
                                    <p class="text-lg font-semibold">Donate Form</p>
                                    <p class="text-xs text-slate-400">Input fill-upped form</p>
                                </div>
                            </div>
                            <div class="col-span-2  px-1 mt-2">
                                <div
                                    class="w-full flex hidden flex-col justify-center items-center h-full p-2 bg-teal-200 rounded-2xl shadow-teal-200 shadow-lg hover:bg-teal-300 cursor-pointer hover:shadow-teal-300 hover:shadow-2xl duration-500">
                                    <div class="w-10 h-10 rounded-full bg-teal-600 p-2 flex flex-col justify-center">
                                        <img src="img/align-text-bottom.png" alt="">
                                    </div>
                                    <p class="text-lg font-semibold">Printable Form</p>
                                    <p class="text-xs text-slate-400 text-center">Downloadable Donor’s Information Form
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class=" flex">
                        <div class="<?php if ($_SESSION['user'] != 'admin')
                            echo 'hidden'; ?> mt-10  height w-96 flex flex-col justify-center justify-between">
                            <p class="font-medium text-lg tracking-wide">Most Recent Donations</p>
                            <div class="p-4 overflow-y-auto hieght w-full flex flex-col relative">
                                <?php
                                if (count($recentdonors) <= 0)
                                    echo '<div class="width-full p-4 text-2xl font-semibold flex justify-center items-center">
                            No donors found :(    
                        </div>
                        '; ?>
                                <?php foreach ($recentdonors as $donor): ?>
                                    <div onclick="span('<?php echo $donor['donor_id']; ?>');" style="width: 300px;"
                                        class="duration-500 ease mx-2 my-2 p-2 h-fit rounded-md bg-white flex items-center justify-between shadow-lg hover:shadow-2xl cursor-pointer duration-500">
                                        <div class="w-full h-full">
                                            <div class="flex relative">
                                                <img src="img/<?php echo $donor['sex']; ?>.png" alt=""
                                                    class="w-10 h-10 rounded-full mx-2 ">
                                                <div class="flex flex-col">
                                                    <p class="text-sm font-semibold truncate">
                                                        <?php echo $donor['fname'] . ' ' . $donor['mname'] . ' ' . $donor['lname']; ?>
                                                    </p>
                                                    <p class="text-xs font-semibold text-slate-400 tracking-tighter">
                                                        <?php echo $donor['donation_date']; ?>
                                                    </p>
                                                </div>
                                                <div
                                                    class="bg-blue-200 absolute right-0 w-8 h-8 p-1 rounded-md flex justify-center items-center mr-2">
                                                    <p class="font-semibold text-sm text-blue-600">
                                                        <?php echo $donor['type'] . $donor['rh']; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="w-full h-fit p-2 px-6 hidden" id="<?php echo $donor['donor_id']; ?>">
                                                <div class="w-full h-fit grid grid-cols-4 gap-2">
                                                    <div class="col-span-1 flex justify-center items-center">
                                                        <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                            <p class="text-sm font-semibold">
                                                                <?php echo $donor['sex']; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-span-3 flex flex-col justify-center items-center">
                                                        <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                            <p class="text-sm font-semibold">
                                                                <?php echo $donor['address']; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="w-full h-fit grid grid-cols-4 mt-2">
                                                    <div class="col-span-4 flex justify-center items-center">
                                                        <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                            <p class="text-sm font-semibold">
                                                                <?php echo $donor['contact']; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="w-full h-fit grid grid-cols-4 mt-2">
                                                    <div class="col-span-4 flex justify-center items-center">
                                                        <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                            <p class="text-sm font-semibold">
                                                                <?php echo $donor['medical_status']; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="w-full h-fit grid grid-cols-4 mt-2">
                                                    <div class="col-span-4 flex justify-center items-center">
                                                        <div class="w-full p-3 bg-white rounded-md shadow-xl">
                                                            <p class="text-sm font-semibold text-red-600">
                                                                <?php echo $donor['donated_bag']; ?> bags
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- End -->
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="<?php if ($_SESSION['user'] != 'admin')
                            echo 'hidden '; ?>deliv-prog mt-10 flex flex-col items-center justify-center  relative pt-10"
                            style="width: 700px;">

                            <p class="mx-2 font-medium text-lg tracking-wide absolute top-0 left-0">Delivery Progress:
                            </p>

                            <div class="h-96 w-full pb-20 px-4 overflow-y-auto">


                                <?php foreach ($progress as $p): ?>
                                    <div class="w-full h-20 p-4 mt-10 bg-slate-500 rounded-2xl my-4 relative">
                                        <div class="w-full items-center flex h-full bg-slate-100 p-2 pr-3 rounded-2xl">
                                            <div class="h-full items-center  rounded-2xl flex relative <?php
                                            if ($p['progress'] == 'Pending') {
                                                echo ' w-1/3 bg-rose-600 ';
                                            } else if ($p['progress'] == 'In Progress') {
                                                echo 'w-2/3 bg-rose-600 ';
                                            } else if ($p['progress'] == 'Cancel') {
                                                echo 'w-full bg-black ';
                                            } else {
                                                echo 'w-full bg-rose-600 ';
                                            }
                                            echo ($p['progress'] == 'Pending') ? 'w-1/3' : 'w-2/3';
                                            ?>">
                                                <div class="ml-10 flex text-xs font-semibold text-white">Requested by:
                                                    <?php echo $p['doctor'] ?>
                                                    <?php if ($p['progress'] == 'Cancel') {
                                                        echo '-<p class="text-rose-600">(Request Cancelled)   </p>';
                                                        echo '-';
                                                        echo $p['name'];
                                                    } ?>
                                                </div>
                                                <img src="img/truck-kun.png" alt="" class="absolute <?php if ($p['progress'] == 'Cancel') {
                                                    echo ' hidden';
                                                } ?>" style="right:-20px;">
                                            </div>
                                            <img src="img/hospital.png" alt="" class="mr-4 absolute mt-1 right-0 top-4"
                                                style="right:-2px;">
                                            <img src="img/request.png" alt="" class="mr-4 absolute left-5 top-5">
                                            <p class="ml-10 text-xs font-semibold">
                                                <?php echo $p['name'] ?>
                                            </p>
                                        </div>
                                        <div class="absolute flex bottom-top-36 justify-between w-full">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>




                        </div>
                    </div>
                </div>
            </div>


    </main>


    <?php
}
?>

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

    function sub(el) {
        document.getElementById(el).click();
        console.log(el);
    }

</script>


<?php require 'partials/foot.php'; ?>