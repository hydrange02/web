<?php
session_start();
include '../Config/Database.php';
$user_id = $_SESSION['user_id'] ?? null;
include '../Compoment/Menu.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
        }

        /* CSS trạng thái chuẩn */
        .status-badge {
            transition: all 0.3s ease;
        }

        .status-processing {
            background-color: #fca5a5;
            color: #b91c1c;
        }

        .status-delivered,
        .status-giao-hàng-thành-công {
            background-color: #a7f3d0;
            color: #047857;
        }

        .status-canceled,
        .status-đã-hủy {
            background-color: #d1d5db;
            color: #4b5563;
        }

        .status-shipped,
        .status-đang-vận-chuyển {
            background-color: #bfdbfe;
            color: #1d4ed8;
        }

        .status-pending,
        .status-đang-chờ-xác-nhận {
            background-color: #fef08a;
            color: #854d0e;
        }
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
                Quản lý và theo dõi đơn hàng của bạn
            </p>
        </div>
    </header>

    <div class="flex flex-col justify-center items-center py-8">
        <div class="w-[95%] bg-white p-5 shadow-xl rounded-xl">
            <?php
            if (!$user_id) {
                echo "<div class='text-center text-xl font-semibold text-red-600 py-10'>Vui lòng đăng nhập để xem lịch sử đơn hàng.</div>";
            } else {
                include '../Compoment/OrderHistory.php';
            }
            ?>
        </div>
    </div>
    <?php if (isset($_SESSION['swal_icon'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['swal_icon'] ?>',
                title: '<?= $_SESSION['swal_title'] ?>',
                text: '<?= $_SESSION['swal_text'] ?>',
                confirmButtonText: 'Tuyệt vời',
                confirmButtonColor: '#3085d6',
                timer: 3000, // Tự tắt sau 3 giây
                timerProgressBar: true
            });
        </script>
        <?php
        // Xóa session ngay sau khi hiện để không lặp lại khi F5
        unset($_SESSION['swal_icon']);
        unset($_SESSION['swal_title']);
        unset($_SESSION['swal_text']);
        ?>
    <?php endif; ?>
</body>

</html>