<?php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. FIX QUYỀN: Cho phép cả Manager xóa (cho đồng bộ với trang Admin_Products)
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa sản phẩm.']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID sản phẩm']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    // 2. BƯỚC QUAN TRỌNG: Xóa sản phẩm khỏi tất cả GIỎ HÀNG trước
    // (Nếu không xóa bước này, dính khóa ngoại với bảng cart sẽ lỗi ngay)
    $stmt_cart = $db->prepare("DELETE FROM cart WHERE item_id = ?");
    $stmt_cart->bind_param("i", $id);
    $stmt_cart->execute();
    $stmt_cart->close();

    // 3. Tiến hành xóa sản phẩm
    $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm thành công!']);
    } else {
        // Nếu execute thất bại (thường do dính order_details)
        throw new Exception($stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $db->rollback();
    
    // Kiểm tra thông báo lỗi để báo người dùng dễ hiểu hơn
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'foreign key constraint') !== false) {
        // Đây là trường hợp sản phẩm đã được mua (nằm trong bảng order_details)
        echo json_encode([
            'success' => false, 
            'message' => 'Không thể xóa: Sản phẩm này đã tồn tại trong các Đơn Hàng cũ. Việc xóa sẽ làm mất lịch sử mua hàng!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $errorMsg]);
    }
}
?>