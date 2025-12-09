<?php
// File: Web php/Config/get_product.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// Kiểm tra quyền (Admin/Manager)
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Lấy thông tin sản phẩm
$stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    echo json_encode(['success' => true, 'data' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
}
?>