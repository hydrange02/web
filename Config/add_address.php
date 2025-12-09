<?php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']); exit; }

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($name) || empty($phone) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']); exit;
}

$db = Database::getInstance()->getConnection();

// Kiểm tra xem user đã có địa chỉ nào chưa, nếu chưa thì set cái này làm mặc định
$check = $db->query("SELECT id FROM user_addresses WHERE user_id = $user_id");
$is_default = ($check->num_rows == 0) ? 1 : 0;

$stmt = $db->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, address, is_default) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $user_id, $name, $phone, $address, $is_default);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã lưu địa chỉ mới']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $db->error]);
}
?>