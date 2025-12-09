<?php
session_start();
include 'Database.php';
header("Content-Type: application/json");

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

$id = $_POST['id'] ?? 0;
$stock = $_POST['stock'] ?? 0;

if ($id <= 0 || $stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE items SET stock = ? WHERE id = ?");
$stmt->bind_param("ii", $stock, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $db->error]);
}
?>