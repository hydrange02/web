<?php
// File: Web php/Compoment/Admin_Menu.php
// Lấy tên file hiện tại để highlight menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="fixed top-0 left-0 z-40 w-64 h-screen bg-slate-900 text-white transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto flex flex-col">
        
        <a href="../Admin_Screen/Admin_Dashboard.php" class="flex items-center pl-2.5 mb-8 mt-2">
            <img src="../assets/web/logo-removebg.png" class="h-10 mr-3 sm:h-12 bg-white rounded-full p-1" alt="Logo" />
            <span class="self-center text-xl font-bold whitespace-nowrap text-blue-400">ADMIN CP</span>
        </a>

        <ul class="space-y-2 font-medium flex-1">
            
            <li>
                <a href="Admin_Dashboard.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Dashboard.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-chart-pie w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3">Tổng Quan</span>
                </a>
            </li>

            <li>
                <a href="Admin_Products.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Products.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-box-open w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3">Sản Phẩm</span>
                </a>
            </li>

            <li>
                <a href="Admin_Orders.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Orders.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-shopping-cart w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3 flex-1 whitespace-nowrap">Đơn Hàng</span>
                    </a>
            </li>

            <li>
                <a href="Admin_Users.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Users.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-users w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3">Khách Hàng</span>
                </a>
            </li>

            <li>
                <a href="Admin_Vouchers.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Vouchers.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-ticket-alt w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3">Voucher</span>
                </a>
            </li>

             <li>
                <a href="Admin_Reviews.php" class="flex items-center p-3 rounded-lg group hover:bg-slate-800 transition <?= $current_page == 'Admin_Reviews.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300' ?>">
                    <i class="fas fa-star w-6 h-6 text-center transition duration-75 group-hover:text-white"></i>
                    <span class="ml-3">Đánh Giá</span>
                </a>
            </li>
        </ul>

        <div class="mt-auto pt-6 border-t border-slate-700">
            <a href="../Config/logout.php" class="flex items-center p-3 text-slate-300 rounded-lg hover:bg-red-600 hover:text-white group transition">
                <i class="fas fa-sign-out-alt w-6 h-6 text-center"></i>
                <span class="ml-3">Đăng Xuất</span>
            </a>
            <div class="mt-4 flex items-center gap-3 px-3">
                <img class="w-8 h-8 rounded-full bg-slate-500" src="https://ui-avatars.com/api/?name=Admin&background=random" alt="Admin Avatar">
                <div class="font-medium text-xs">
                    <div class="text-white">Administrator</div>
                    <div class="text-slate-400">Quản trị viên</div>
                </div>
            </div>
        </div>
    </div>
</aside>