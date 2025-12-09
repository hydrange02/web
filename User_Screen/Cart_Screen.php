<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes bounce-slow {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .animate-bounce-slow {
            animation: bounce-slow 3s infinite;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php
    session_start();
    include '../Config/Database.php';
    $user_id = $_SESSION['user_id'] ?? null;
    include '../Compoment/Menu.php';
    ?>

    <header class="h-[140px] w-full flex flex-col items-center justify-center bg-gradient-to-r from-blue-700 to-cyan-500 shadow-lg relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
        <h1 class="text-3xl font-extrabold text-white drop-shadow-md animate-bounce-slow">Giỏ Hàng Của Bạn</h1>
        <p class="text-blue-100 mt-2 text-sm">Kiểm tra lại các món đồ yêu thích trước khi thanh toán</p>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <?php if (!$user_id): ?>
            <div class="bg-white p-8 rounded-xl shadow-md text-center">
                <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" class="w-32 mx-auto mb-4 opacity-50">
                <p class="text-xl font-semibold text-gray-600 mb-4">Vui lòng đăng nhập để xem giỏ hàng</p>
                <a href="../index.php" class="px-6 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition">Đăng nhập ngay</a>
            </div>
        <?php else: ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4 hidden md:grid grid-cols-12 gap-4 p-4 text-gray-500 font-bold uppercase text-xs tracking-wider items-center">
                <div class="col-span-5 pl-4 flex items-center gap-4">
                    <input type="checkbox" id="select-all" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer transition" title="Chọn tất cả">
                    <span>Sản Phẩm</span>
                </div>
                <div class="col-span-2 text-center">Đơn Giá</div>
                <div class="col-span-2 text-center">Số Lượng</div>
                <div class="col-span-2 text-center">Thành Tiền</div>
                <div class="col-span-1 text-center">Xóa</div>
            </div>

            <?php include '../Compoment/Cart.php'; ?>

        <?php endif; ?>
    </div>
</body>

</html>