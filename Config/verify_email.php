<?php
// File: Web php/Config/verify_email.php
session_start();
include 'Database.php';

$token = $_GET['token'] ?? '';
$status = 'error'; // Mặc định là lỗi
$message = 'Link không hợp lệ hoặc thiếu Token.';
$title = 'Xác Thực Thất Bại';
$icon = 'fa-times-circle';
$color = 'text-red-500';

if (!empty($token)) {
    $db = Database::getInstance()->getConnection();

    // 1. Tìm user
    $stmt = $db->prepare("SELECT id, token_expire, is_verified FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ($user['is_verified'] == 1) {
            $status = 'info';
            $title = 'Đã Kích Hoạt';
            $message = 'Tài khoản của bạn đã được kích hoạt trước đó rồi.';
            $icon = 'fa-info-circle';
            $color = 'text-blue-500';
        } elseif (strtotime($user['token_expire']) < time()) {
            $status = 'expired';
            $title = 'Link Hết Hạn';
            $message = 'Link xác thực đã hết hạn (chỉ có hiệu lực trong 24h).';
            $icon = 'fa-clock';
            $color = 'text-orange-500';
        } else {
            // Cập nhật Database
            $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expire = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            
            if ($update->execute()) {
                $status = 'success';
                $title = 'Xác Thực Thành Công!';
                $message = 'Tài khoản của bạn đã được kích hoạt. Bạn có thể đăng nhập ngay bây giờ.';
                $icon = 'fa-check-circle';
                $color = 'text-green-500';
            } else {
                $message = 'Lỗi hệ thống khi cập nhật trạng thái.';
            }
        }
    } else {
        $message = 'Link xác thực không tồn tại hoặc đã được sử dụng.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Email - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #1e3a8a, #3b82f6, #06b6d4, #0ea5e9);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 450px;
            padding: 40px;
            text-align: center;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.8s forwards;
        }
        @keyframes slideUp { to { transform: translateY(0); opacity: 1; } }

        .icon-box {
            font-size: 80px;
            margin-bottom: 20px;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.5s forwards;
            transform: scale(0);
            display: inline-block;
        }
        @keyframes popIn { to { transform: scale(1); } }
    </style>
</head>
<body>

    <div class="card">
        <div class="icon-box <?= $color ?>">
            <i class="fas <?= $icon ?>"></i>
        </div>
        
        <h1 class="text-2xl font-extrabold text-gray-800 mb-2"><?= $title ?></h1>
        <p class="text-gray-600 mb-8 leading-relaxed"><?= $message ?></p>

        <a href="../index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-full transition-all transform hover:scale-105 shadow-lg hover:shadow-blue-500/50">
            <?php echo ($status === 'success' || $status === 'info') ? 'Đăng Nhập Ngay' : 'Quay Về Trang Chủ'; ?>
        </a>
    </div>

    <?php if ($status === 'success'): ?>
    <script>
        // Hiệu ứng pháo hoa khi thành công
        window.onload = function() {
            var duration = 3 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function randomInRange(min, max) { return Math.random() * (max - min) + min; }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();
                if (timeLeft <= 0) return clearInterval(interval);
                var particleCount = 50 * (timeLeft / duration);
                
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        };
    </script>
    <?php endif; ?>

</body>
</html>