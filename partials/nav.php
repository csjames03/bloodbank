<nav class="h-screen w-72 md:flex flex-col fixed hidden border-r">
    <div class="w-full p-2 flex justify-center items-center flex-col">
        <p class="text-2xl font-medium tracking-wider">
            <span class="text-rose-500">B</span>lood <span class="text-rose-500">B</span>ank
        </p>
        <p class="text-sm font-medium tracking-wide">
            <span class="text-rose-500">M</span>anagement <span class="text-rose-500">S</span>ystem
        </p>
    </div>
    <div class="mt-6 px-8 flex flex-col z-100 ">
        <div class="w-full  mb-4 flex items-center">
            <img src="img/dashboardicon.png" alt="Dashboard Icon" class="w-7">
            <a href="/bloodbank/"
                class="<?php echo ($url === '/bloodbank/') ? 'text-rose-500' : 'text-slate-500' ?> font-medium ml-4 cursor-pointer">Dashboard</a>
        </div>
        <div class="w-full  my-4 flex items-center <?php if ($_SESSION['user'] != 'admin')
            echo "hidden" ?> ">
            <img src="img/stocksicon.png" alt="Dashboard Icon" class="w-7">
            <a href="./stocks" class="<?php echo ($url === '/bloodbank/stocks') ? 'text-rose-500' : 'text-slate-500' ?>
                font-medium ml-4 cursor-pointer hover:text-rose-300">Stocks</a>
        </div>
        <div class="w-full  my-4 flex items-center <?php if ($_SESSION['user'] != 'admin')
            echo "hidden" ?> ">
            <img src="img/requestsicon.png" alt="Dashboard Icon" class="w-7">
            <a href="./requests"
                class="<?php echo ($url === '/bloodbank/requests') ? 'text-rose-500' : 'text-slate-500' ?> font-medium ml-4 cursor-pointer hover:text-rose-300">Requests</a>
        </div>
        <div class="w-full  my-4 flex items-center <?php if ($_SESSION['user'] != 'admin')
            echo "hidden" ?> ">
            <img src="img/donationsicon.png" alt="Dashboard Icon" class="w-7">
            <a href="./donations"
                class="<?php echo ($url === '/bloodbank/donations') ? 'text-rose-500' : 'text-slate-500' ?> font-medium ml-4 cursor-pointer hover:text-rose-300">
                Donations</a>
        </div>
        <div class="w-full  my-4 flex items-center ">
            <img src="img/logout.png" alt="Logout Icon" class="w-7">
            <a href="./logout" class="text-slate-500 font-medium ml-4 cursor-pointer hover:text-rose-300">
                Logout </a>
        </div>
    </div>
    <div class="">
        <img src="img/Vector 5.png" alt="" class="absolute background top-96">
        <img src="img/Vector 8.png" alt="" class="absolute background top-96">
        <img src="img/sidebarnavchar.png" alt="" class=" fixed background bottom-0">
    </div>
</nav>