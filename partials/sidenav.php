<?php require_once './head.php' ?>
<?php require_once './navbar.php' ?>
<nav class="h-screen w-72 flex flex-col fixed">
    <div class="w-full p-2 flex justify-center items-center flex-col">
        <p class="text-2xl font-medium tracking-wider">
            <span class="text-rose-500">B</span>lood <span"text-rose-500">B</span>ank
        </p>
        <p class="text-sm font-medium tracking-wide">
            <span class="text-rose-500">M</span>anagement <span"text-rose-500">S</span>ystem
        </p>
    </div>
    <div class="mt-20 px-8 flex flex-col z-100">
        <div class="w-full  mb-4 flex items-center">
            <img src="../public/img/dashboardicon.png" alt="Dashboard Icon" class="w-7">
            <p class="text-rose-500 font-medium ml-4 cursor-pointer">Dashboard</p>
        </div>
        <div class="w-full  my-4 flex items-center">
            <img src="../public/img/stocksicon.png" alt="Dashboard Icon" class="w-7">
            <p class="text-slate-500 font-medium ml-4 cursor-pointer hover:text-rose-300">Stocks</p>
        </div>
        <div class="w-full  my-4 flex items-center">
            <img src="../public/img/requestsicon.png" alt="Dashboard Icon" class="w-7">
            <p class="text-slate-500 font-medium ml-4 cursor-pointer hover:text-rose-300">Requests</p>
        </div>
        <div class="w-full  my-4 flex items-center">
            <img src="../public/img/donationsicon.png" alt="Dashboard Icon" class="w-7">
            <p class="text-slate-500 font-medium ml-4 cursor-pointer hover:text-rose-300">Donations</p>
        </div>
    </div>
    <div class="">
        <img src="../public/img/Vector 5.png" alt="" class="absolute background top-80">
        <img src="../public/img/Vector 8.png" alt="" class="absolute background top-80">
        <img src="../public/img/sidebarnavchar.png" alt="" class=" fixed background top-96">
    </div>
</nav>