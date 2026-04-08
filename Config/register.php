<?php
ob_start();
error_reporting(0); // Tắt hiển thị lỗi trực tiếp để không làm hỏng JSON
ini_set('display_errors', 0);
header("Content-Type: application/json");
set_time_limit(120);

try {
    session_start();
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/MailHelper.php'; 

    $db = Database::getInstance()->getConnection(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Nhận dữ liệu từ Frontend
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // 2. Validate cơ bản
    if (empty($email) || empty($username) || empty($password)) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Vui lòng nhập đủ thông tin!"]); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Định dạng Email không hợp lệ!"]); exit;
    }
    if ($password !== $confirm) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Mật khẩu xác nhận không khớp!"]); exit;
    }
    if (strlen($username) < 6 || strlen($password) < 6) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Username và mật khẩu phải từ 6 ký tự!"]); exit;
    }

    // 3. KIỂM TRA TỒN TẠI (Username & Email)
    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
    if (!$checkEmail) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Lỗi chuẩn bị SQL: " . $db->error]); exit;
    }
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Email này đã được sử dụng!"]); exit;
    }
    $checkEmail->close();

    // 4. Nếu chưa tồn tại -> Tạo tài khoản mới
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32)); 
    $expire = date('Y-m-d H:i:s', strtotime('+1 day')); 
    $default_role = 'user';
    $is_verified = 0;

    $stmt = $db->prepare("INSERT INTO users (username, password, email, role, is_verified, verification_token, token_expire) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Lỗi chuẩn bị SQL (Insert): " . $db->error]); exit;
    }
    $stmt->bind_param("ssssiss", $username, $hashed_password, $email, $default_role, $is_verified, $token, $expire);

    if ($stmt->execute()) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $currentDir = dirname($_SERVER['PHP_SELF']); 
        $verifyLink = "$protocol://$host$currentDir/verify_email.php?token=$token";

        $subject = "Xác thực tài khoản đăng ký";
        $title = "Chào mừng $username!";
        $bodyContent = "
            <p>Cảm ơn bạn đã đăng ký tài khoản tại Hydrange Shop.</p>
            <p>Để kích hoạt tài khoản, vui lòng nhấn vào nút bên dưới:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$verifyLink' style='background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; display: inline-block;'>
                    Kích Hoạt Tài Khoản
                </a>
            </div>
        ";

        $mailResult = MailHelper::sendCustomMail($email, $subject, $title, $bodyContent);
        
        // Xóa sạch tất cả các cấp độ đệm đầu ra (tránh ký tự lạ)
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Content-Type: application/json");
        if (isset($mailResult['success']) && $mailResult['success']) {
            echo json_encode(["success"=>true, "message"=>"Vui lòng kiểm tra Hòm thư để kích hoạt tài khoản."], JSON_UNESCAPED_UNICODE);
        } else {
            $errorMsg = $mailResult['message'] ?? 'Lỗi không xác định';
            echo json_encode(["success"=>false, "message"=>"Đăng ký thành công nhưng không gửi được email. Lỗi: " . $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        exit;

    } else {
        ob_clean();
        echo json_encode(["success"=>false, "message"=>"Lỗi hệ thống: " . $stmt->error]);
        exit;
    }
    $stmt->close();
}

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
    exit;
}
// Không có thẻ đóng ?> để tránh ký tự dư thừa