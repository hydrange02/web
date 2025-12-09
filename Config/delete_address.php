<?php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']); exit; }

$id = $_POST['id'] ?? 0;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã xóa địa chỉ']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
}
?>