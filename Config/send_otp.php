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
    // --- BẮT ĐẦU SỬA ĐỔI: Soạn nội dung và gửi mail bằng hàm mới ---
    
    $subject = "Mã xác thực đổi mật khẩu";
    $title = "Yêu Cầu Đổi Mật Khẩu";
    
    // Soạn nội dung HTML cho phần thân email
    $bodyContent = "
        <p>Xin chào,</p>
        <p>Chúng tôi nhận được yêu cầu thay đổi mật khẩu cho tài khoản của bạn.</p>
        <p>Vui lòng sử dụng mã xác thực (OTP) dưới đây để tiếp tục:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <span style='background-color: #e0f2fe; color: #0284c7; padding: 15px 30px; font-size: 24px; font-weight: bold; letter-spacing: 5px; border-radius: 8px; border: 1px dashed #0284c7;'>
                $otp
            </span>
        </div>
        
        <p style='color: #ef4444; font-size: 14px;'><em>Mã này sẽ hết hạn sau 15 phút. Tuyệt đối không chia sẻ cho người lạ.</em></p>
    ";

    // Gọi hàm sendCustomMail từ MailHelper mới
    $result = MailHelper::sendCustomMail($user['email'], $subject, $title, $bodyContent);
    
    // Kiểm tra kết quả trả về (dạng mảng ['success' => true/false, ...])
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Mã OTP đã được gửi tới email: ' . $user['email']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gửi mail thất bại: ' . $result['message']]);
    }
    // --- KẾT THÚC SỬA ĐỔI ---

} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
?>