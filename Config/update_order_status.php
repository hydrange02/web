<?php
// File: Web php/Config/update_order_status.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. KIỂM TRA QUYỀN
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập!']);
    exit;
}

$order_id = $_POST['order_id'] ?? null;
$new_status = $_POST['status'] ?? '';
$allowed_statuses = ['Đang chờ xác nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy'];

if (!$order_id || !in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    // 2. Lấy thông tin đơn hàng hiện tại (để lấy user_id, tổng tiền và trạng thái cũ)
    // Dùng FOR UPDATE để khóa dòng này tránh xung đột khi nhiều admin cùng thao tác
    $query = $db->prepare("SELECT user_id, total_amount, status FROM orders WHERE id = ? FOR UPDATE");
    $query->bind_param("i", $order_id);
    $query->execute();
    $order = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$order) {
        throw new Exception("Đơn hàng không tồn tại.");
    }

    $old_status = $order['status'];
    $customer_id = $order['user_id'];
    $total_money = $order['total_amount'];
    $points_added = 0;

    // 3. LOGIC CỘNG ĐIỂM
    // Chỉ cộng khi chuyển từ trạng thái KHÁC sang "Đã giao hàng"
    if ($old_status !== 'Đã giao hàng' && $new_status === 'Đã giao hàng') {
        // Quy đổi: 10.000 VNĐ = 1 điểm
        $points_added = floor($total_money / 10000);

        if ($points_added > 0) {
            $pt_stmt = $db->prepare("UPDATE users SET current_points = current_points + ? WHERE id = ?");
            $pt_stmt->bind_param("ii", $points_added, $customer_id);
            if (!$pt_stmt->execute()) {
                throw new Exception("Lỗi khi cộng điểm.");
            }
            $pt_stmt->close();
        }
    }

    // 4. LOGIC TRỪ ĐIỂM (Nếu Admin lỡ tay bấm "Đã giao hàng" rồi chuyển lại trạng thái khác)
    // Nếu trạng thái cũ là "Đã giao hàng" mà chuyển sang cái khác -> Thu hồi điểm
    if ($old_status === 'Đã giao hàng' && $new_status !== 'Đã giao hàng') {
        $points_removed = floor($total_money / 10000);
        if ($points_removed > 0) {
            // Dùng GREATEST để đảm bảo điểm không bị âm
            $rm_stmt = $db->prepare("UPDATE users SET current_points = GREATEST(0, current_points - ?) WHERE id = ?");
            $rm_stmt->bind_param("ii", $points_removed, $customer_id);
            $rm_stmt->execute();
            $rm_stmt->close();
        }
    }

    // 5. Cập nhật trạng thái đơn hàng
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        $db->commit();
        $msg = "Đã cập nhật đơn #$order_id thành '$new_status'";
        if ($points_added > 0) {
            $msg .= ". Khách hàng được cộng $points_added điểm.";
        }
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        throw new Exception($db->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>  