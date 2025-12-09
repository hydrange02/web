<?php
// File: Web php/Config/delete_order.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// KIỂM TRA QUYỀN: Chỉ Admin và Manager được xóa
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    // 1. Xóa chi tiết đơn hàng trước (order_details)
    $stmt1 = $db->prepare("DELETE FROM order_details WHERE order_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Xóa đơn hàng (orders)
    $stmt2 = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt2->bind_param("i", $id);
    
    if ($stmt2->execute()) {
        $db->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($db->error);
    }
    $stmt2->close();

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>