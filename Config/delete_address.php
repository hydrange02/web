<?php
session_start();
include 'Database.php';
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // QUAN TRỌNG: Thêm 'AND user_id = ?' để đảm bảo chính chủ
    // (Giả sử bảng tên là user_address hoặc addresses, hãy sửa tên bảng cho đúng với DB của bạn)
    $stmt = $db->prepare("DELETE FROM user_address WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa địa chỉ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy địa chỉ hoặc không có quyền xóa']);
        }
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>