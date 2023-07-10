<?php require 'partials/head.php'; ?>

<?php
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
                <p class="text-2xl font-bold tracking-wide text-rose-600">Admin Portal</p>
                <input type="text" name="name"
                    class="mt-10 text-lg font-semibold focus:outline placeholder:font-normal focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 w-11/12 p-3 mt-4 h-12 rounded-md bg-slate-200 shadow-lg"
                    placeholder="Admin Username" value="<?php if ($submmited)
                        echo $name ?>">
                    <p id="errorname" class="text-red-500 text-medium font-semibold flex self-start mx-6 <?php if ($adminNameExist)
                        echo 'hidden'; ?>">
                    Incorrect name!
                </p>
                <input type="password" name="password"
                    class="text-lg font-semibold placeholder:font-normal focus:outline  focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 w-11/12 mt-4 p-3 h-12 rounded-md bg-slate-200 shadow-lg"
                    placeholder="Enter your password" value="<?php if ($submmited)
                        echo $password ?>">
                    <p id=" errorid" class="text-red-500 text-medium <?php if ($adminNameExist && $passwordCorrect)
                        echo 'hidden'; ?> font-semibold flex self-start mx-6">
                    Incorrect password!
                </p>
                <input type="submit" value="Login" name="submit"
                    class="focus:outline  focus:outline-rose-600 focus:outline-3 focus:outline-offset-3 mt-10 w-56  hover:w-60 p-3 py-4 bg-rose-600 cursor-pointer duration-500 rounded-md shadow-xl hover: hover:shadow-2xl hover:shadow-rose-700 hadow-rose-400 font-semibold text-white tracking-wide">
            </div>

        </div>
    </form>
    <?php
} else {
    header("Location: /bloodbank/");
}