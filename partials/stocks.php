<?php require_once './head.php' ?>
<?php require_once './navbar.php' ?>
<?php require_once './sidenav.php' ?>

<main class="mt-16 ml-72 fixed h-fit  overflow-hidden p-2 flex flex-col " style="width:89rem;">
    <p class="font-medium text-slate-900 text-lg">Stock</p>
    <div class="w-5/6 h-36 bg-yellow-100 ml-20 rounded-2xl">
        <div class="flex px-4 py-2 items-center">
            <p class=" font-medium text-lg text-slate-600">In Stock</p>
            <p class="flex  items-center ml-32">
            <p class="mx-1 text-sm font-medium text-slate-600">15</p>
            <p class="mx-1 text-sm font-medium text-slate-600">March</p>
            <p class="mx-1 text-sm font-medium text-slate-600">2022</p>
            </p>
        </div>
        <div class="flex px-4 py-1 items-center">
            <div class="flex flex-col justify-center items-center text-yellow-500 mt-2">
                <p class="text-lg">Total</p>
                <p class="text-5xl font-bold">12</p>
            </div>
            <div class="flex ml-28">
                <div class="flex flex-col mx-1">
                    <div class="w-8 h-8 bg-yellow-200 rounded-md z-20 flex justify-center items-center">
                        <p class="text-white text-medium font-medium">B+</p>
                    </div>
                    <div class="w-8 h-8 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                        <p class="text-yellow-200 text-sm font-medium mt-2">5</p>
                    </div>
                </div>
                <div class="flex flex-col mx-1">
                    <div class="w-8 h-8 bg-yellow-200 rounded-md z-20 flex justify-center items-center">
                        <p class="text-white text-medium font-medium">B+</p>
                    </div>
                    <div class="w-8 h-8 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                        <p class="text-yellow-200 text-sm font-medium mt-2">5</p>
                    </div>
                </div>
                <div class="flex flex-col mx-1">
                    <div class="w-8 h-8 bg-yellow-200 rounded-md z-20 flex justify-center items-center">
                        <p class="text-white text-medium font-medium">B+</p>
                    </div>
                    <div class="w-8 h-8 bg-white rounded-md overlap absolute mt-5 flex justify-center items-center">
                        <p class="text-yellow-200 text-sm font-medium mt-2">5</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p class="font-medium text-slate-900 text-lg">Requests</p>
    <div class="flex">
        <div class="w-fit h-fit p-2">
            <div class="flex">
                <div
                    class="w-44 h-52 bg-white shadow-lg mx-2 my-4 rounded-2xl cursor-pointer hover:shadow-2xl flex flex-col justify-center items-center">
                    <div class="w-14 h-14 rounded-full bg-slate-300 p-2 flex justify-center items-center">

                    </div>
                    <p class="text-2xl font-medium mt-2">A</p>
                </div>
                <div
                    class="w-44 h-52 bg-white shadow-lg mx-2 my-4 rounded-2xl cursor-pointer hover:shadow-2xl  flex flex-col justify-center items-center">
                    <div class="w-14 h-14 rounded-full bg-slate-300 p-2 flex justify-center items-center">

                    </div>
                    <p class="text-2xl font-medium mt-2">B</p>
                </div>
            </div>
            <div class="flex">
                <div
                    class="w-44 h-52 bg-white shadow-lg mx-2 my-4 rounded-2xl cursor-pointer hover:shadow-2xl  flex flex-col justify-center items-center">
                    <div class="w-14 h-14 rounded-full bg-slate-300 p-2 flex justify-center items-center">

                    </div>
                    <p class="text-2xl font-medium mt-2">AB</p>
                </div>
                <div
                    class="w-44 h-52 bg-white shadow-lg mx-2 my-4 rounded-2xl cursor-pointer hover:shadow-2xl  flex flex-col justify-center items-center">
                    <div class="w-14 h-14 rounded-full bg-slate-300 p-2 flex justify-center items-center">

                    </div>
                    <p class="text-2xl font-medium mt-2">0</p>
                </div>
            </div>
        </div>
        <div class="h-fit overflow-y-auto bg-yellow-100 rounded-2xl p-5 grid grid-cols-3 gap-4"
            style="width:62rem; height:32rem;">
            <?php
            for ($i = 0; $i < 150; $i++) {
                echo '
                <div class="py-10 h-20 rounded-xl bg-slate-100 flex items-center px-4 justify-between cursor-pointer">
                    <div class="flex">
                        <img src="../public/img/profile.png" alt="" class="w-10">
                        <div class="mx-4 flex flex-col justify-center items-center">
                            <p class="font-bold text-slate-900 text-md">Elon Musk</p>
                            <p class="font-medium tracking-tighter text-slate-900 text-xs">15 March 2022</p>
                        </div>
                    </div>
                    <div class="w-8 h-8 bg-rose-300 rounded-md z-20 flex justify-center items-center">
                        <p class="text-white text-medium font-medium">B+</p>
                    </div>
                </div>
                ';
            }
            ?>
        </div>
    </div>

</main>