<?php
// File: Web php/Config/upload_product_image.php
session_start();
include 'CloudinaryHelper.php'; // Sử dụng lại Helper có sẵn
header("Content-Type: application/json");

// Kiểm tra quyền Admin/Manager
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file hợp lệ.']);
    exit;
}

// Upload lên Cloudinary
$result = CloudinaryHelper::uploadImage($_FILES['file']['tmp_name']);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'url' => $result['url'] // Trả về link ảnh để Frontend hiển thị
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi upload: ' . $result['message']]);
}
?>