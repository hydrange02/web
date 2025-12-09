<?php
session_start();
include 'Database.php';
header("Content-Type: application/json");

// 1. Kiểm tra quyền (Admin hoặc Manager mới được thêm)
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit;
}

// 2. Nhận dữ liệu
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$stock = $_POST['stock'] ?? 0;
$brand = $_POST['brand'] ?? '';
$category = $_POST['category'] ?? ''; // Nhận từ input (đã được JS xử lý)
$image = $_POST['image'] ?? '';
$description = $_POST['description'] ?? '';

// 3. Validate dữ liệu cơ bản
if (empty($name) || empty($price) || empty($category) || empty($image)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc (Tên, Giá, Danh mục, Ảnh)']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // 4. Chuẩn bị câu lệnh SQL (Prepared Statement)
    $stmt = $db->prepare("INSERT INTO items (name, price, stock, brand, category, image, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Bind tham số: s (string), i (integer), d (double)
    // name(s), price(d), stock(i), brand(s), category(s), image(s), description(s)
    $stmt->bind_param("sdissss", $name, $price, $stock, $brand, $category, $image, $description);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?>