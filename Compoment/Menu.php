<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .group:hover .group-hover\:block { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        body { padding-top: 80px; } /* Tránh menu che nội dung */
    </style>
</head>
<body>
    <?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    $role = $_SESSION['role'] ?? 'user';
    $is_admin = in_array($role, ['admin', 'manager']);
    $current_page = basename($_SERVER['PHP_SELF']); // Lấy tên file hiện tại

    // Định nghĩa Class cho menu Active và Inactive để code gọn hơn
    $menu_active = "bg-slate-800 border-l-4 border-blue-500 text-blue-400 font-bold shadow-inner";
    $menu_normal = "text-gray-300 hover:bg-slate-800 hover:text-white border-l-4 border-transparent";
    ?>

    <?php if ($is_admin): ?>
        <aside class="fixed top-0 left-0 h-screen w-64 bg-[#1e293b] text-white flex flex-col z-50 shadow-2xl transition-all duration-300">
            <div class="h-20 flex items-center justify-center border-b border-slate-700 bg-[#0f172a] shadow-md">
                <h1 class="font-extrabold text-xl tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">
                    <i class="fas fa-shield-alt mr-2"></i>ADMIN
                </h1>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-6 space-y-2">
                <p class="px-6 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Quản lý chung</p>
                
                <a href="../Admin_Screen/Admin_Dashboard.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= $current_page == 'Admin_Dashboard.php' ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-chart-line w-5 text-center"></i> Thống Kê
                </a>

                <a href="../Admin_Screen/Admin_Products.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= $current_page == 'Admin_Products.php' ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-box-open w-5 text-center"></i> Sản Phẩm
                </a>

                <a href="../Admin_Screen/Admin_Orders.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= strpos($current_page, 'Order') !== false ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-file-invoice-dollar w-5 text-center"></i> Đơn Hàng
                </a>

                <a href="../Admin_Screen/Admin_Vouchers.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= $current_page == 'Admin_Vouchers.php' ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-ticket-alt w-5 text-center"></i> Voucher
                </a>

                <a href="../Admin_Screen/Admin_Reviews.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= $current_page == 'Admin_Reviews.php' ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-comments w-5 text-center"></i> Đánh Giá
                </a>

                <a href="../Admin_Screen/Admin_Users.php" 
                   class="block px-6 py-3 flex items-center gap-3 transition-all duration-200 <?= $current_page == 'Admin_Users.php' ? $menu_active : $menu_normal ?>">
                    <i class="fas fa-users w-5 text-center"></i> Người Dùng
                </a>
            </nav>

            <div class="p-4 border-t border-slate-700 bg-[#0f172a]">
                <a href="../Config/logout.php" class="flex items-center justify-center gap-2 w-full py-3 bg-red-600/20 text-red-400 rounded-lg hover:bg-red-600 hover:text-white transition-all duration-300 font-bold text-sm">
                    <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                </a>
            </div>
        </aside>

    <?php else: ?>
        <nav class="fixed top-0 left-0 w-full bg-white/95 backdrop-blur-md shadow-sm z-50 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <a href="../User_Screen/Home_Screen.php" class="flex items-center gap-2 group">
                        <img src="../assets/web/logo-removebg.png" class="h-12 w-auto transition-transform group-hover:scale-110">
                        <span class="font-extrabold text-2xl text-blue-600 tracking-tight">Hydrange</span>
                    </a>

                    <div class="hidden md:flex items-center space-x-8">
                        <a href="../User_Screen/Home_Screen.php" class="font-bold text-gray-600 hover:text-blue-600 transition <?= $current_page == 'Home_Screen.php' ? 'text-blue-600' : '' ?>">Trang Chủ</a>
                        <a href="../User_Screen/Home_Screen.php?sort=newest" class="font-bold text-gray-600 hover:text-blue-600 transition">Sản Phẩm</a>
                        <a href="../User_Screen/History_Screen.php" class="font-bold text-gray-600 hover:text-blue-600 transition <?= $current_page == 'History_Screen.php' ? 'text-blue-600' : '' ?>">Đơn Hàng</a>
                    </div>

                    <div class="flex items-center gap-6">
                        <a href="../User_Screen/Cart_Screen.php" class="relative text-gray-600 hover:text-blue-600 transition transform hover:scale-110">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <?php
                            $cart_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                include_once '../Config/Database.php';
                                $db = Database::getInstance()->getConnection();
                                $c_res = $db->query("SELECT COUNT(*) as c FROM cart WHERE user_id = {$_SESSION['user_id']}");
                                $cart_count = $c_res->fetch_assoc()['c'];
                            }
                            if ($cart_count > 0) echo "<span class='absolute -top-2 -right-2 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center animate-bounce'>$cart_count</span>";
                            ?>
                        </a>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            // LOGIC LẤY ĐIỂM CHUẨN
                            $uid = $_SESSION['user_id'];
                            $u_res = $db->query("SELECT username, img, current_points FROM users WHERE id = $uid");
                            $u_data = $u_res->fetch_assoc();
                            $name = htmlspecialchars($u_data['username']);
                            $points = number_format($u_data['current_points']);
                            $img = $u_data['img'] ?: "https://ui-avatars.com/api/?name=$name&background=0D8ABC&color=fff";
                            ?>
                            
                            <div class="relative group h-full flex items-center cursor-pointer">
                                <div class="hidden md:flex flex-col items-end mr-3">
                                    <span class="text-sm font-bold text-gray-700"><?= $name ?></span>
                                    <span class="text-[10px] font-bold text-yellow-600 bg-yellow-50 px-2 rounded border border-yellow-100"><?= $points ?> pts</span>
                                </div>
                                <div class="py-4"> <img src="<?= $img ?>" class="w-10 h-10 rounded-full border border-gray-200 object-cover ring-2 ring-transparent group-hover:ring-blue-300 transition">
                                </div>

                                <div class="absolute top-[85%] right-0 w-60 pt-2 hidden group-hover:block animate-fade-in-down">
                                    <div class="bg-white rounded-xl shadow-xl py-2 border border-gray-100 overflow-hidden relative">
                                        <div class="absolute -top-2 right-4 w-4 h-4 bg-white border-t border-l border-gray-100 transform rotate-45"></div>

                                        <a href="../User_Screen/Account_Screen.php" class="flex items-center px-5 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600 text-sm font-medium transition">
                                            <i class="fas fa-user-circle w-6"></i> Hồ sơ cá nhân
                                        </a>
                                        <a href="../User_Screen/VoucherWallet_Screen.php" class="flex items-center px-5 py-3 text-gray-600 hover:bg-orange-50 hover:text-orange-600 text-sm font-bold transition">
                                            <i class="fas fa-ticket-alt w-6"></i> Kho Voucher
                                        </a>
                                        <div class="border-t border-gray-100 mt-2 pt-2">
                                            <a href="../Config/logout.php" class="flex items-center px-5 py-3 text-red-500 hover:bg-red-50 hover:text-red-600 text-sm font-bold transition">
                                                <i class="fas fa-sign-out-alt w-6"></i> Đăng xuất
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="../index.php" class="bg-blue-600 text-white px-6 py-2.5 rounded-full font-bold text-sm shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Đăng Nhập</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>
</body>
</html>