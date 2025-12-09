<?php
// File: Web php/Config/user_cancel_order.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$order_id = $_POST['order_id'] ?? null;
if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng.']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // 2. Kiểm tra quyền sở hữu và trạng thái đơn hàng
    // Chỉ cho phép hủy nếu đơn hàng thuộc về user đó VÀ trạng thái là 'Đang chờ xác nhận'
    $checkStmt = $db->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $order_id, $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $order = $result->fetch_assoc();
    $checkStmt->close();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại hoặc không thuộc về bạn.']);
        exit;
    }

    if ($order['status'] !== 'Đang chờ xác nhận') {
        echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng này (Đã được xử lý hoặc đang vận chuyển).']);
        exit;
    }

    // 3. Thực hiện hủy đơn (Cập nhật trạng thái thành 'Đã hủy')
    $updateStmt = $db->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ?");
    $updateStmt->bind_param("i", $order_id);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $db->error]);
    }
    $updateStmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>