<?php
$today = date('Y-m-d');

require 'partials/head.php';

require 'partials/nav.php';
?>

<main class="fixed overflow-hidden h-full md:ml-72 md:p-16 py-16 flex flex-col h-fit w-fit" style="width:85rem;">
    <p class="mx-4 my-3 font-bold text-2xl">Stocks Information</p>
    <div class="w-full  grid grid-cols-9 gap-5 p-10 " style="height:530px;">
        <div
            class="bg-blue-200 relative h-36 col-span-3 flex p-4 flex-col rounded-xl hover:shadow-2xl duration-300 hover:shadow-blue-200">
            <div class="w-full flex items-center relative">
                <p class=" text-blue-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-blue-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-blue-600 font-medium">Total</p>
                        <p class="text-3xl text-blue-600 font-bold">
                            <?php echo $bloodrecieved['a+']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">
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

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div
            class="relative bg-stone-200 h-36 col-span-3 p-4 rounded-xl hover:shadow-2xl duration-300 hover:shadow-stone-200">
            <div class="w-full flex items-center relative">
                <p class=" text-stone-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-stone-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-stone-600 font-medium">Total</p>
                        <p class="text-3xl text-stone-600 font-bold">
                            <?php echo $bloodrecieved['a-']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-stone-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-stone-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">A-</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-stone-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-rose-200 h-36 col-span-3 rounded-xl hover:shadow-2xl duration-300 hover:shadow-rose-200">
            <div class="w-full flex items-center relative">
                <p class=" text-rose-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-rose-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-rose-600 font-medium">Total</p>
                        <p class="text-3xl text-rose-600 font-bold">
                            <?php echo $bloodrecieved['b+']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

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

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-cyan-200 h-36 col-span-4 rounded-xl hover:shadow-2xl duration-300 hover:shadow-cyan-200">
            <div class="w-full flex items-center relative">
                <p class=" text-cyan-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-cyan-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-cyan-600 font-medium">Total</p>
                        <p class="text-3xl text-cyan-600 font-bold">
                            <?php echo $bloodrecieved['b-']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-cyan-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-cyan-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">B-</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-cyan-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-green-200 h-36 col-span-4 rounded-xl hover:shadow-2xl duration-300 hover:shadow-green-200">
            <div class="w-full flex items-center relative">
                <p class=" text-green-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-green-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-green-600 font-medium">Total</p>
                        <p class="text-3xl text-green-600 font-bold">
                            <?php echo $bloodrecieved['o+']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-green-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-green-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">O+</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-green-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-yellow-200 h-36 col-span-3 rounded-xl hover:shadow-2xl duration-300 hover:shadow-teal-200">
            <div class="w-full flex items-center relative">
                <p class=" text-teal-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-teal-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-teal-600 font-medium">Total</p>
                        <p class="text-3xl text-teal-600 font-bold">
                            <?php echo $bloodrecieved['o-']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-teal-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-teal-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">O-</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-teal-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-teal-200 h-36 col-span-3 rounded-xl hover:shadow-2xl duration-300 hover:shadow-teal-200">
            <div class="w-full flex items-center relative">
                <p class=" text-teal-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-teal-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-teal-600 font-medium">Total</p>
                        <p class="text-3xl text-teal-600 font-bold">
                            <?php echo $bloodrecieved['ab+']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-teal-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-teal-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">AB+</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-teal-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
        <div class="bg-lime-200 h-36 col-span-3 rounded-xl hover:shadow-2xl duration-300 hover:shadow-lime-200">
            <div class="w-full flex items-center relative">
                <p class=" text-lime-600 font-semibold text-xl ml-4 mt-3 margin">Stocks</p>
                <p class="text-lime-600 font font-semibold text-sm  lg:mt-1 mr-2 absolute right-0">
                    <?php echo $today ?>
                </p>
            </div>
            <div class="w-full h-fit flex ">
                <div class="w-3/12 mt-5">
                    <div class="flex flex-col mt-1 md:mx-4 ml-4">
                        <p class="text-sm text-lime-600 font-medium">Total</p>
                        <p class="text-3xl text-lime-600 font-bold">
                            <?php echo $bloodrecieved['ab-']; ?>
                        </p>
                    </div>
                </div>
                <div class="w-9/12 mt-2  grid grid-cols-3 gap-1 px-4">
                    <div class="flex flex-col mx-1">

                    </div>
                    <div class="flex flex-col mx-1">
                        <div
                            class="w-10 h-10 bg-lime-400 rounded-md z-20 flex justify-center items-center  hover:pb-2 hover:bg-lime-600 cursor-pointer duration-300">
                            <p class="text-white text-sm font-medium">AB-</p>
                        </div>
                        <div
                            class="w-10 h-10 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                            <p class="text-lime-400 text-sm font-medium mt-3">
                                <?php

                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-1">


                    </div>

                </div>

            </div>
        </div>
    </div>

</main>