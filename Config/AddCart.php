<?php
// File: Web php/Config/AddCart.php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'Database.php'; 

$db = Database::getInstance()->getConnection();

$user_id = $_SESSION['user_id'] ?? null;

// Kiểm tra đăng nhập
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng.', 'require_login' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($item_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    // --- SỬA ĐỔI QUAN TRỌNG: LẤY GIÁ TỪ DATABASE ---
    // Không nhận $_POST['price'] nữa để tránh hack giá
    $stmt_price = $db->prepare("SELECT price FROM items WHERE id = ?");
    $stmt_price->bind_param("i", $item_id);
    $stmt_price->execute();
    $res_price = $stmt_price->get_result();
    
    if ($res_price->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại.']);
        exit;
    }
    
    $product = $res_price->fetch_assoc();
    $real_price = floatval($product['price']); // Giá chính xác từ hệ thống
    $stmt_price->close();
    // -----------------------------------------------

    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $check = $db->prepare("SELECT id, quantity FROM cart WHERE item_id = ? AND user_id = ?");
    $check->bind_param("ii", $item_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    $cart_id = 0;

    if ($result->num_rows > 0) {
        // --- ĐÃ CÓ -> CẬP NHẬT ---
        $row = $result->fetch_assoc();
        $cart_id = $row['id'];
        $newQty = $row['quantity'] + $quantity;
        $newTotal = $newQty * $real_price; // Dùng giá chuẩn tính lại tổng

        $update = $db->prepare("UPDATE cart SET quantity = ?, total = ? WHERE id = ?");
        $update->bind_param("idi", $newQty, $newTotal, $cart_id);
        
        if (!$update->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi SQL Update: ' . $db->error]);
            exit;
        }
        $update->close();
    } else {
        // --- CHƯA CÓ -> THÊM MỚI ---
        $total = $real_price * $quantity;
        $stmt = $db->prepare("INSERT INTO cart (user_id, item_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $user_id, $item_id, $quantity, $real_price, $total);
        
        if ($stmt->execute()) {
            $cart_id = $stmt->insert_id;
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi SQL Insert: ' . $db->error]);
            exit;
        }
        $stmt->close();
    }
    
    $check->close();

    echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng thành công!']);
}
?>