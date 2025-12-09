<?php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$order_id = $_POST['order_id'] ?? null;

if (!$user_id || !$order_id) {
    echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu']);
    exit;
}

$db = Database::getInstance()->getConnection();
// Chỉ ẩn đơn hàng của chính user đó
$stmt = $db->prepare("UPDATE orders SET is_hidden = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL']);
}
?>