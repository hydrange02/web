<?php
// File: Web php/Config/user_cancel_order.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
$order_id = $_POST['order_id'] ?? null;

if (!$user_id || !$order_id) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // 1. Kiểm tra đơn hàng
    $check = $db->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $order_id, $user_id);
    $check->execute();
    $order = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$order || $order['status'] !== 'Đang chờ xác nhận') {
        throw new Exception('Không thể hủy đơn hàng này.');
    }

    // 2. [QUAN TRỌNG] Lấy danh sách sản phẩm để HOÀN KHO
    $sql_items = "SELECT item_id, quantity FROM order_details WHERE order_id = ?";
    $stmt_items = $db->prepare($sql_items);
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    // Chuẩn bị câu lệnh cộng lại kho
    $sql_restock = "UPDATE items SET stock = stock + ? WHERE id = ?";
    $stmt_restock = $db->prepare($sql_restock);

    while ($item = $result_items->fetch_assoc()) {
        $stmt_restock->bind_param("ii", $item['stock'], $item['item_id']);
        $stmt_restock->execute();
    }
    
    $stmt_items->close();
    $stmt_restock->close();

    // 3. Cập nhật trạng thái 'Đã hủy'
    $update = $db->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ?");
    $update->bind_param("i", $order_id);
    $update->execute();
    $update->close();

    echo json_encode(['success' => true, 'message' => 'Đã hủy đơn và hoàn kho thành công.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>