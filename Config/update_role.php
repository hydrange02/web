<?php
// File: Web php/Config/update_role.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. BẢO MẬT TUYỆT ĐỐI: Chỉ role 'admin' mới được thực thi file này
$current_role = $_SESSION['role'] ?? 'user';
if ($current_role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'CẢNH BÁO: Bạn không có quyền Admin để thực hiện thao tác này!']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$new_role = $_POST['role'] ?? '';

// Validate dữ liệu
if (!$user_id || !in_array($new_role, ['user', 'manager', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

// Không cho phép tự thay đổi quyền của chính mình (để tránh admin tự giáng chức mình rồi mất quyền)
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Không thể tự thay đổi quyền của chính mình.']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Đã cập nhật quyền thành công!"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Lỗi Database: " . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>