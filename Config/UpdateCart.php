<?php
// File: Web php/Config/UpdateCart.php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = intval($_POST['cart_id'] ?? 0);
    $action = $_POST['action'] ?? ''; // 'increase' hoặc 'decrease'

    if ($cart_id <= 0 || !in_array($action, ['increase', 'decrease'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    // 1. Lấy thông tin hiện tại của item trong giỏ
    $stmt = $db->prepare("SELECT quantity, price FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    $currentQty = $item['quantity'];
    $price = $item['price'];
    $newQty = $currentQty;

    // 2. Tính toán số lượng mới
    if ($action === 'increase') {
        $newQty++;
    } else {
        $newQty--;
    }

    // Không cho phép số lượng < 1
    if ($newQty < 1) {
        echo json_encode(['success' => false, 'message' => 'Số lượng tối thiểu là 1']);
        exit;
    }

    // 3. Cập nhật vào DB
    $newTotal = $newQty * $price;
    $update = $db->prepare("UPDATE cart SET quantity = ?, total = ? WHERE id = ?");
    $update->bind_param("iii", $newQty, $newTotal, $cart_id);
    
    if ($update->execute()) {
        echo json_encode([
            'success' => true,
            'new_qty' => $newQty,
            'new_total' => $newTotal, // Trả về tổng tiền mới của item này
            'item_price' => $price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật DB']);
    }
}
?>