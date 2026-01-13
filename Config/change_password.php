<?php
// File: Web php/Config/change_password.php
session_start();
header('Content-Type: application/json');
include 'Database.php'; 

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { 
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']); 
    exit; 
}

// --- KIỂM TRA CSRF TOKEN ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Lỗi bảo mật CSRF!']); 
    exit;
}

$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// 1. Validate dữ liệu đầu vào
if (empty($old_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mật khẩu cũ và mật khẩu mới.']); 
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']); 
    exit;
}

$db = Database::getInstance()->getConnection();

// 2. Lấy mật khẩu hiện tại từ DB
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Kiểm tra Mật khẩu cũ
if (!password_verify($old_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không chính xác.']); 
    exit;
}

// 4. Cập nhật mật khẩu mới
// (Tiện thể xóa luôn reset_token cũ nếu có để đảm bảo an toàn)
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
$stmt->bind_param("si", $new_hashed_password, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi cập nhật.']);
}
$stmt->close();
?>