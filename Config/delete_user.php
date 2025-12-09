<?php
// File: Web php/Config/delete_user.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. CHỈ ADMIN MỚI ĐƯỢC XÓA
$current_role = $_SESSION['role'] ?? 'user';
if ($current_role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền Admin!']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;

// 2. Validate
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

// 3. Không cho phép tự xóa chính mình
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Không thể tự xóa tài khoản của chính mình!']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    // 4. Xóa dữ liệu liên quan trước (Giỏ hàng, Đánh giá)
    $stmt1 = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $db->prepare("DELETE FROM reviews WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stmt2->close();

    // 5. Xóa User
    // Lưu ý: Nếu user đã có đơn hàng (bảng orders), lệnh này có thể lỗi do khóa ngoại.
    // Nếu muốn xóa cả User có đơn hàng, phải xóa Order trước (nhưng thường không nên xóa lịch sử kinh doanh).
    $stmt3 = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt3->bind_param("i", $user_id);
    
    if ($stmt3->execute()) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Đã xóa người dùng thành công!"]);
    } else {
        throw new Exception($stmt3->error);
    }
    $stmt3->close();

} catch (Exception $e) {
    $db->rollback();
    // Kiểm tra nếu lỗi do khóa ngoại (User đã có đơn hàng)
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo json_encode(['success' => false, 'message' => "Không thể xóa: Người dùng này đã có lịch sử mua hàng."]);
    } else {
        echo json_encode(['success' => false, 'message' => "Lỗi hệ thống: " . $e->getMessage()]);
    }
}
?>