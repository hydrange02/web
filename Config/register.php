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

    // Check Email (Đây là đoạn bạn yêu cầu: Kiểm tra email có trong DB chưa)
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
        // 5. Gửi mail xác thực
        if (MailHelper::sendVerificationEmail($email, $token)) {
            echo json_encode(["success"=>true, "message"=>"Đăng ký thành công! Vui lòng kiểm tra Email để kích hoạt tài khoản."]);
        } else {
            // Trường hợp lưu DB được nhưng gửi mail lỗi -> Vẫn báo thành công nhưng cảnh báo
            echo json_encode(["success"=>true, "message"=>"Đăng ký thành công nhưng không gửi được email. Hãy thử đăng nhập và yêu cầu gửi lại."]);
        }
    } else {
        echo json_encode(["success"=>false, "message"=>"Lỗi hệ thống: " . $stmt->error]);
    }
    $stmt->close();
}
?>