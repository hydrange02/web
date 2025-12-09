<?php
session_start();
// Đảm bảo đường dẫn này đúng tới file kết nối database của bạn
include '../Config/Database.php';

// Lấy ID người dùng từ session
$user_id = $_SESSION['user_id'] ?? null;

// Menu bên trái (Đã sửa lỗi hover)
include '../Compoment/Menu.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS tùy chỉnh cho trạng thái đơn hàng */
        .status-processing {
            background-color: #fca5a5;
            color: #b91c1c;
        }

        /* Red-300 */
        .status-delivered {
            background-color: #a7f3d0;
            color: #047857;
        }

        /* Green-300 */
        .status-canceled {
            background-color: #d1d5db;
            color: #4b5563;
        }

        /* Gray-300 */
        .status-shipped {
            background-color: #bfdbfe;
            color: #1d4ed8;
        }

        /* Blue-300 */
    </style>
</head>

<body class="bg-gray-100">
    <header class="h-[150px] w-full flex items-center justify-center bg-gradient-to-r from-purple-600 to-pink-500 relative overflow-hidden rounded-b-xl shadow-lg">
        <div class="absolute inset-0 bg-gradient-to-r from-white/10 via-white/20 to-white/10 animate-pulse"></div>
        <div class="relative z-10 text-center">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white drop-shadow-lg">
                Lịch Sử Đơn Hàng
            </h1>
            <p class="text-lg md:text-xl text-white/90 mt-2 drop-shadow-md">
                Xem lại các đơn hàng bạn đã đặt.
            </p>
        </div>
    </header>

    <div class="flex flex-col justify-center items-center py-8">
        <div class="w-[95%] bg-white p-5 shadow-xl rounded-xl">

            <?php
            if (!$user_id) {
                echo "<div class='text-center text-xl font-semibold text-red-600'>Vui lòng đăng nhập để xem lịch sử đơn hàng.</div>";
            } else {
                // Bao gồm file xử lý lấy dữ liệu đơn hàng
                include '../Compoment/OrderHistory.php';
            }
            ?>

        </div>
    </div>
</body>

</html>