<?php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']); exit; }

// Nhận dữ liệu
$id = $_POST['id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate cơ bản
if (empty($id) || empty($name) || empty($phone) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']); exit;
}

// Validate số điện thoại (10-11 số)
if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']); exit;
}

$db = Database::getInstance()->getConnection();

// Cập nhật
$stmt = $db->prepare("UPDATE user_addresses SET recipient_name = ?, phone = ?, address = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sssii", $name, $phone, $address, $id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật địa chỉ thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $db->error]);
}
?>