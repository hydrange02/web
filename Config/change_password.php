<?php
// File: Web php/Config/change_password.php
session_start();
header('Content-Type: application/json');
include 'Database.php'; 

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']); exit; }

// --- KIỂM TRA CSRF TOKEN ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Lỗi bảo mật CSRF!']); exit;
}

$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$otp_input    = $_POST['otp'] ?? ''; // <--- NHẬN MÃ OTP

if (empty($old_password) || empty($new_password) || empty($otp_input)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin và mã OTP.']); exit;
}

$db = Database::getInstance()->getConnection();

// 1. Lấy thông tin User và Token từ DB
$stmt = $db->prepare("SELECT password, reset_token, reset_expire FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Kiểm tra Mật khẩu cũ
if (!password_verify($old_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không chính xác.']); exit;
}

// 3. KIỂM TRA OTP (QUAN TRỌNG)
if ($user['reset_token'] !== $otp_input) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP không đúng.']); exit;
}

// 4. Kiểm tra hết hạn
if (strtotime($user['reset_expire']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP đã hết hạn. Vui lòng lấy mã mới.']); exit;
}

// 5. Cập nhật mật khẩu mới và XÓA OTP
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
$stmt->bind_param("si", $new_hashed_password, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
$stmt->close();
?>