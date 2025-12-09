<?php
// File: Web php/Config/send_otp.php
session_start();
include 'Database.php';
include 'MailHelper.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Lấy email người dùng
$stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || empty($user['email'])) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản chưa có Email. Vui lòng cập nhật Email trước!']);
    exit;
}

$otp = rand(100000, 999999);
$expire = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$update = $db->prepare("UPDATE users SET reset_token = ?, reset_expire = ? WHERE id = ?");
$update->bind_param("ssi", $otp, $expire, $user_id);

if ($update->execute()) {
    // Gọi hàm gửi mail đẹp mới cập nhật
    $sent = MailHelper::sendOtpEmail(
        $user['email'], 
        $otp, 
        "Xác nhận đổi mật khẩu", 
        "Chúng tôi nhận được yêu cầu đổi mật khẩu cho tài khoản của bạn. Dưới đây là mã xác thực:"
    );
    
    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Mã OTP đã được gửi tới email: ' . $user['email']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gửi mail thất bại.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
?>