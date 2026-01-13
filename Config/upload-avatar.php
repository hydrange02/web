<?php
session_start();
include 'Database.php';
include 'CloudinaryHelper.php'; // Load helper mới

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

// Kiểm tra có file gửi lên không
if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file ảnh hợp lệ.']);
    exit;
}

// 1. Upload lên Cloudinary
$fileTmp = $_FILES['avatar_file']['tmp_name'];
$result = CloudinaryHelper::uploadImage($fileTmp);

if (!$result['success']) {
    echo json_encode($result); // Trả về lỗi nếu upload thất bại
    exit;
}

$imageUrl = $result['url']; // Đây là link ảnh đầy đủ (https://...)

// 2. Lưu URL vào Database
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE users SET img = ? WHERE id = ?");
$stmt->bind_param('si', $imageUrl, $user_id);

if ($stmt->execute()) {
    // Trả về đường dẫn để Frontend hiển thị
    echo json_encode([
        'success' => true, 
        'message' => 'Đổi ảnh đại diện thành công!', 
        'path' => $imageUrl // Trả về URL full
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu Database.']);
}
?>