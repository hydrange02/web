<?php
session_start();
include 'Database.php';
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success'=>false, 'message'=>'Vui lòng đăng nhập']); exit; }

$item_id = $_POST['item_id'] ?? 0;
$rating = $_POST['rating'] ?? 5;
$comment = trim($_POST['comment'] ?? '');

if (!$item_id || empty($comment)) {
    echo json_encode(['success'=>false, 'message'=>'Vui lòng nhập nội dung đánh giá']); exit;
}

$db = Database::getInstance()->getConnection();

// 1. Kiểm tra xem user đã mua sản phẩm và ĐÃ NHẬN HÀNG chưa
$checkBuy = $db->prepare("
    SELECT o.id 
    FROM orders o 
    JOIN order_details od ON o.id = od.order_id 
    WHERE o.user_id = ? 
    AND od.item_id = ? 
    AND o.status = 'Đã giao hàng'  -- Chỉ cho phép khi đã giao hàng
    LIMIT 1
");
$checkBuy->bind_param("ii", $user_id, $item_id);
$checkBuy->execute();

if ($checkBuy->get_result()->num_rows === 0) {
    echo json_encode(['success'=>false, 'message'=>'Bạn chỉ được đánh giá sản phẩm đã mua và đã nhận hàng thành công!']);
    exit;
}

// 2. Thêm đánh giá
$stmt = $db->prepare("INSERT INTO reviews (user_id, item_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $user_id, $item_id, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Cảm ơn bạn đã đánh giá!']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Lỗi hệ thống']);
}
?>