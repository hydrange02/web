<?php
// File: Web php/Config/edit_product.php
session_start();
include 'Database.php';
header("Content-Type: application/json");

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện']);
    exit;
}

// Nhận dữ liệu từ Form
$id = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$brand = $_POST['brand'] ?? '';
$category = $_POST['category'] ?? '';
$image = $_POST['image'] ?? '';
$description = $_POST['description'] ?? '';

// Validate
if (!$id || empty($name) || empty($price) || empty($category) || empty($image)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Cập nhật thông tin (Trừ stock vì stock đã có nút sửa nhanh riêng)
    $stmt = $db->prepare("UPDATE items SET name=?, price=?, brand=?, category=?, image=?, description=? WHERE id=?");
    $stmt->bind_param("sdssssi", $name, $price, $brand, $category, $image, $description, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công!']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>