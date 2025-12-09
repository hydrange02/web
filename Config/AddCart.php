<?php
// File: Web php/Config/AddCart.php
session_start();
include 'Database.php'; 
header('Content-Type: application/json'); // Đổi thành JSON

$db = Database::getInstance()->getConnection();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $price = intval($_POST['price'] ?? 0); 
    
    // Tính tổng tiền
    $total = $price * $quantity;

    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }
    
    // Kiểm tra tồn tại trong giỏ
    $check = $db->prepare("SELECT id, quantity FROM cart WHERE item_id = ? AND user_id = ?");
    $check->bind_param("ii", $item_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    $cart_id = 0;

    if ($result->num_rows > 0) {
        // --- CẬP NHẬT ---
        $row = $result->fetch_assoc();
        $cart_id = $row['id']; // Lấy ID giỏ hàng cũ
        $newQty = $row['quantity'] + $quantity;
        $newTotal = $newQty * $price; 

        $update = $db->prepare("UPDATE cart SET quantity = ?, total = ? WHERE id = ?");
        $update->bind_param("iii", $newQty, $newTotal, $cart_id);
        $update->execute();
        $update->close();
    } else {
        // --- THÊM MỚI ---
        $stmt = $db->prepare("INSERT INTO cart (user_id, item_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiii", $user_id, $item_id, $quantity, $price, $total);
        $stmt->execute();
        $cart_id = $stmt->insert_id; // Lấy ID giỏ hàng vừa tạo
        $stmt->close();
    }
    
    $check->close();

    // Trả về cart_id cho Frontend
    echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ!', 'cart_id' => $cart_id]);
}
?>