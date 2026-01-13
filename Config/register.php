<?php
// File: Web php/Config/register.php
session_start();
include './Database.php';
include './MailHelper.php'; 
header("Content-Type: application/json");

$db = Database::getInstance()->getConnection(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Nhận dữ liệu từ Frontend
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // 2. Validate cơ bản
    if (empty($email) || empty($username) || empty($password)) {
        echo json_encode(["success"=>false, "message"=>"Vui lòng nhập đủ thông tin!"]); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success"=>false, "message"=>"Định dạng Email không hợp lệ!"]); exit;
    }
    if ($password !== $confirm) {
        echo json_encode(["success"=>false, "message"=>"Mật khẩu xác nhận không khớp!"]); exit;
    }
    if (strlen($username) < 6 || strlen($password) < 6) {
        echo json_encode(["success"=>false, "message"=>"Username và mật khẩu phải từ 6 ký tự!"]); exit;
    }

    // 3. KIỂM TRA TỒN TẠI (Username & Email)
    
    // Check Username
    $checkUser = $db->prepare("SELECT id FROM users WHERE username = ?");
    $checkUser->bind_param('s', $username);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        echo json_encode(["success"=>false, "message"=>"Tên đăng nhập đã tồn tại!"]); exit;
    }
    $checkUser->close();

    // Check Email
    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        echo json_encode(["success"=>false, "message"=>"Email này đã được sử dụng! Vui lòng dùng email khác."]); exit;
    }
    $checkEmail->close();

    // 4. Nếu chưa tồn tại -> Tạo tài khoản mới
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Tạo mã xác thực
    $token = bin2hex(random_bytes(32)); 
    $expire = date('Y-m-d H:i:s', strtotime('+1 day')); 
    $default_role = 'user';
    $is_verified = 0; // Mặc định chưa xác thực

    $stmt = $db->prepare("INSERT INTO users (username, password, email, role, is_verified, verification_token, token_expire) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiss", $username, $hashed_password, $email, $default_role, $is_verified, $token, $expire);

    if ($stmt->execute()) {
        
        // --- SỬA ĐỔI: Soạn nội dung và gọi hàm sendCustomMail ---
        
        // Tạo link xác thực (Chú ý sửa domain/thư mục cho đúng với máy của bạn)
        // Ví dụ: http://localhost/Web%20php/Config/verify_email.php?token=...
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = "/Web%20php/Config/verify_email.php"; // Hãy kiểm tra kỹ đường dẫn này trên máy bạn
        $verifyLink = "$protocol://$host$path?token=$token";

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

        // Gọi hàm sendCustomMail đã có trong MailHelper
        $mailResult = MailHelper::sendCustomMail($email, $subject, $title, $bodyContent);
        
        if ($mailResult['success']) {
            echo json_encode(["success"=>true, "message"=>"Đăng ký thành công! Vui lòng kiểm tra Email để kích hoạt tài khoản."]);
        } else {
            echo json_encode(["success"=>true, "message"=>"Đăng ký thành công nhưng không gửi được email. Lỗi: " . $mailResult['message']]);
        }
        // --- KẾT THÚC SỬA ĐỔI ---

    } else {
        echo json_encode(["success"=>false, "message"=>"Lỗi hệ thống: " . $stmt->error]);
    }
    $stmt->close();
}
?>