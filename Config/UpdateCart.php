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
    $action = $_POST['action'] ?? ''; // 'increase', 'decrease' hoặc 'update'

    if ($cart_id <= 0 || !in_array($action, ['increase', 'decrease', 'update'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    // 1. Lấy thông tin hiện tại của item trong giỏ và tồn kho
    $stmt = $db->prepare("SELECT c.quantity, c.price, i.stock FROM cart c JOIN items i ON c.item_id = i.id WHERE c.id = ? AND c.user_id = ?");
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
    $stock = $item['stock'];
    $newQty = $currentQty;

    // 2. Tính toán số lượng mới
    if ($action === 'increase') {
        $newQty++;
    } else if ($action === 'decrease') {
        $newQty--;
    } else if ($action === 'update') {
        $newQty = intval($_POST['quantity'] ?? 1);
    }

    // Không cho phép số lượng < 1
    if ($newQty < 1) {
        $newQty = 1;
    }

    // Không cho phép vượt quá số lượng tồn kho
    if ($newQty > $stock) {
        echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá sản phẩm có sẵn (Còn ' . $stock . ' sản phẩm)', 'new_qty' => $stock]);
        exit;
    }

    // Nếu không có thay đổi
    if ($newQty == $currentQty && $action == 'update') {
        echo json_encode([
            'success' => true,
            'new_qty' => $newQty,
            'new_total' => $newQty * $price,
            'item_price' => $price
        ]);
        exit;
    }

    // 3. Cập nhật vào DB
    $newTotal = $newQty * $price;
    $update = $db->prepare("UPDATE cart SET quantity = ?, total = ? WHERE id = ?");
    $update->bind_param("iii", $newQty, $newTotal, $cart_id);
    
    if ($update->execute()) {
        // Cập nhật lại cache giỏ hàng trong session
        $cQuery = $db->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id");
        $cRow = $cQuery->fetch_assoc();
        $_SESSION['cart_count'] = intval($cRow['total'] ?? 0);

        echo json_encode([
            'success' => true,
            'new_qty' => $newQty,
            'new_total' => $newTotal,
            'item_price' => $price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật DB']);
    }
}
?>