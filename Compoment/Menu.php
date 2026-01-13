<nav class="bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            
            <div class="flex-shrink-0 flex items-center gap-3 cursor-pointer group" onclick="window.location.href='../User_Screen/Home_Screen.php'">
                <div class="relative">
                    <img class="h-12 w-12 object-contain transition-transform duration-500 group-hover:rotate-12" 
                         src="../assets/web/logo-removebg.png" 
                         onerror="this.src='https://cdn-icons-png.flaticon.com/512/3081/3081986.png'" 
                         alt="Logo">
                    <div class="absolute -bottom-1 -right-1 bg-green-500 w-3 h-3 rounded-full border-2 border-white"></div>
                </div>
                <div class="hidden md:block">
                    <span class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500 tracking-tight">HYDRANGE</span>
                    <span class="block text-[10px] text-gray-400 font-medium tracking-widest uppercase -mt-1">Grocery Store</span>
                </div>
            </div>

            <div class="hidden md:flex space-x-10 items-center">
                <a href="../User_Screen/Home_Screen.php" class="text-gray-600 hover:text-blue-600 font-bold text-sm uppercase tracking-wide transition relative group">
                    Trang chủ
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../User_Screen/VoucherWallet_Screen.php" class="text-gray-600 hover:text-orange-500 font-bold text-sm uppercase tracking-wide transition flex items-center gap-1 group">
                        <i class="fas fa-ticket-alt"></i> Kho Voucher
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-orange-500 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="../User_Screen/History_Screen.php" class="text-gray-600 hover:text-blue-600 font-bold text-sm uppercase tracking-wide transition relative group">
                        Đơn hàng
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-6">
                <a href="../User_Screen/Cart_Screen.php" class="relative group p-2 rounded-full hover:bg-blue-50 transition">
                    <i class="fas fa-shopping-bag text-2xl text-gray-600 group-hover:text-blue-600 transition"></i>
                    <?php 
                        // Logic đếm giỏ hàng
                        $cart_count = 0;
                        if(isset($_SESSION['user_id'])) {
                            // Giả định class Database đã được include ở file cha
                            $dbConn = Database::getInstance()->getConnection();
                            $uid = $_SESSION['user_id'];
                            
                            // Kiểm tra bảng 'carts' tồn tại (đã sửa tên bảng từ 'cart' thành 'carts' cho đúng chuẩn với các file khác nếu cần, hoặc giữ nguyên nếu bảng là 'cart')
                            // Ở đây tôi dùng 'cart' theo code gốc của bạn, nếu lỗi hãy đổi thành 'carts'
                            $cQuery = $dbConn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
                            if ($cQuery) {
                                $cRow = $cQuery->fetch_assoc();
                                $cart_count = $cRow['total'] ?? 0;
                            }
                        }
                    ?>
                    <?php if($cart_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm animate-bounce">
                            <?= $cart_count > 9 ? '9+' : $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="relative ml-2 group">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                            // Lấy thông tin img mới nhất từ DB để đảm bảo đồng bộ (phòng trường hợp session chưa update)
                            $userId = $_SESSION['user_id'];
                            $dbConn = Database::getInstance()->getConnection();
                            $userQuery = $dbConn->prepare("SELECT img, username, role FROM users WHERE id = ?");
                            $userQuery->bind_param("i", $userId);
                            $userQuery->execute();
                            $userResult = $userQuery->get_result();
                            $userData = $userResult->fetch_assoc();
                            
                            // Ưu tiên lấy từ DB, nếu không có thì lấy từ Session, cuối cùng là ảnh mặc định
                            $imgUrl = !empty($userData['img']) ? $userData['img'] : 
                                         (!empty($_SESSION['img']) ? $_SESSION['img'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png');
                            
                            // Kiểm tra nếu là đường dẫn nội bộ (không chứa http) thì thêm prefix
                            if (strpos($imgUrl, 'http') === false) {
                                $imgUrl = '../assets/uploads/imgs/' . $imgUrl;
                            }
                            
                            $username = $userData['username'] ?? $_SESSION['username'] ?? 'User';
                            $role = $userData['role'] ?? $_SESSION['role'] ?? 'user';
                        ?>
                        <button class="flex items-center gap-2 focus:outline-none">
                            <img class="h-10 w-10 rounded-full object-cover border-2 border-gray-100 shadow-sm group-hover:border-blue-300 transition" 
                                 src="<?= htmlspecialchars($imgUrl) ?>" 
                                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
                                 alt="Avatar">
                        </button>
                        <div class="absolute right-0 mt-4 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right z-50 top-full">
                            
                            <div class="absolute -top-2 right-4 w-4 h-4 bg-white border-t border-l border-gray-100 transform rotate-45"></div>

                            <div class="px-5 py-3 border-b border-gray-50 bg-gray-50/50 rounded-t-xl">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Tài khoản</p>
                                <p class="font-bold text-gray-800 truncate"><?= htmlspecialchars($username) ?></p>
                            </div>
                            
                            <a href="../User_Screen/Account_Screen.php" class="flex items-center px-5 py-3 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition">
                                <i class="fas fa-user-circle w-6"></i> Hồ sơ cá nhân
                            </a>
                            
                            <?php if ($role === 'admin'): ?>
                                <a href="../Admin_Screen/Admin_Dashboard.php" class="flex items-center px-5 py-3 text-sm text-red-600 hover:bg-red-50 transition font-medium">
                                    <i class="fas fa-shield-alt w-6"></i> Trang Quản Trị
                                </a>
                            <?php endif; ?>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <a href="../Config/logout.php" class="flex items-center px-5 py-3 text-sm text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
                                <i class="fas fa-sign-out-alt w-6"></i> Đăng xuất
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="../index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full font-bold text-sm shadow-lg hover:shadow-blue-500/30 transition transform hover:-translate-y-0.5">
                            Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>