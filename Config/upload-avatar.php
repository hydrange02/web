<?php
session_start();
// Đảm bảo đường dẫn này đúng (nằm cùng thư mục Config)
include 'Database.php'; 

header("Content-Type: application/json");

// 1. Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
    exit;
}

// 2. Nhận dữ liệu ảnh
$imageUrl = $_POST['imageUrl'] ?? '';

// Kiểm tra dữ liệu rỗng
if (empty($imageUrl)) {
    echo json_encode(['success' => false, 'message' => 'Không nhận được đường dẫn ảnh.']);
    exit;
}

// 3. Kết nối DB và Cập nhật
$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("UPDATE users SET img = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn: " . $db->error);
    }

    $stmt->bind_param('si', $imageUrl, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật ảnh đại diện thành công!']);
    } else {
        throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>