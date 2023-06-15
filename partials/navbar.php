<?php require_once './head.php' ?>

<nav class="w-screen h-16 border-b-2 border-slate-200 p-2 flex items-center justify-end bg-transparent fixed">
    <form action="">
        <div class="h-12 p-2 w-96 bg-slate-300 rounded-3xl flex flex-end cursor-pointer">
            <img src="../public/img/searchicon.png" alt="" class="h-5 flex self-center ml-4">
            <input type="text" placeholder="Search..."
                class="h-full w-full outline-0 bg-transparent ml-4 p-2 text-rose-500">
            <input type="submit" class="hidden">
        </div>
    </form>
    <div class=" w-12 h-12 bg-slate-200 flex justify-center items-center rounded-full ml-4 cursor-pointer transition
        duration-700 hover:bg-slate-300 ease-in-out ">
        <img src=" ../public/img/notifbuttonblack.png" alt="Bell Icon" class="h-4">
    </div>
    <div
        class="w-12 h-12 bg-slate-200 flex justify-center items-center rounded-full mx-4 cursor-pointer transition duration-700 hover:bg-slate-300 ease-in-out ">
        <img src="../public/img/settingsicon.png" alt="Bell Icon" class="h-4">
    </div>

</nav>