<?php
// File: Web php/Config/forgot_password.php
session_start();
include 'Database.php';
include 'MailHelper.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

if ($action === 'send_otp') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']); exit;
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email này chưa được đăng ký!']); exit;
    }

    $otp = rand(100000, 999999);
    $expire = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $update = $db->prepare("UPDATE users SET reset_token = ?, reset_expire = ? WHERE email = ?");
    $update->bind_param("sss", $otp, $expire, $email);
    
    if ($update->execute()) {
        // Gọi hàm gửi mail đẹp
        $sent = MailHelper::sendOtpEmail(
            $email, 
            $otp, 
            "Khôi phục tài khoản", 
            "Bạn đang thực hiện yêu cầu lấy lại mật khẩu. Hãy sử dụng mã bên dưới để đặt lại mật khẩu mới:"
        );

        if ($sent) {
            echo json_encode(['success' => true, 'message' => 'Đã gửi mã OTP! Vui lòng kiểm tra email.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi gửi email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
    }
}

elseif ($action === 'reset_pass') {
    // Logic đặt lại mật khẩu giữ nguyên như cũ
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($otp) || empty($new_pass)) { echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']); exit; }
    if ($new_pass !== $confirm) { echo json_encode(['success' => false, 'message' => 'Mật khẩu không khớp']); exit; }
    if (strlen($new_pass) < 6) { echo json_encode(['success' => false, 'message' => 'Mật khẩu quá ngắn']); exit; }

    $check = $db->prepare("SELECT id, reset_expire FROM users WHERE email = ? AND reset_token = ?");
    $check->bind_param("ss", $email, $otp);
    $check->execute();
    $res = $check->get_result();
    $user = $res->fetch_assoc();

    if (!$user) { echo json_encode(['success' => false, 'message' => 'Mã OTP không chính xác!']); exit; }
    if (strtotime($user['reset_expire']) < time()) { echo json_encode(['success' => false, 'message' => 'Mã OTP đã hết hạn!']); exit; }

    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
    $update->bind_param("si", $hash, $user['id']);

    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật.']);
    }
}
?>