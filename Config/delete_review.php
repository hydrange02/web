<?php
// File: Web php/Config/delete_review.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// Kiểm tra quyền: Chỉ Admin/Manager được xóa
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đánh giá']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa đánh giá thành công!']);
    } else {
        throw new Exception($db->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>