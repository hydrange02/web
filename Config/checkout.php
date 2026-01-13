<?php
// File: Web php/Config/checkout.php
session_start();
include 'Database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
    die("Lỗi bảo mật.");
}

// Nhận dữ liệu chung
$receiver_name = trim($_POST['receiver_name'] ?? '');
$receiver_phone = trim($_POST['receiver_phone'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'COD';
$voucher_code = trim($_POST['voucher_code'] ?? '');
$shipping_fee = intval($_POST['shipping_fee'] ?? 0);

// Kiểm tra thông tin bắt buộc
if (empty($receiver_name) || empty($receiver_phone) || empty($shipping_address)) {
    echo "<script>alert('Thiếu thông tin người nhận!'); history.back();</script>";
    exit;
}

$db = Database::getInstance()->getConnection();

// Xác định loại đơn hàng (Mua ngay hay Từ giỏ)
$action = $_POST['action'] ?? '';
$cart_ids = [];
$order_items = []; // Danh sách sản phẩm sẽ mua

try {
    $db->begin_transaction();

    // --- TRƯỜNG HỢP 1: MUA NGAY ---
    if ($action === 'buynow') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($item_id <= 0 || $quantity <= 0) throw new Exception("Dữ liệu sản phẩm không hợp lệ.");

        // Lấy thông tin sản phẩm trực tiếp từ DB
        $stmt = $db->prepare("SELECT id, name, price, stock FROM items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        if (!$item) throw new Exception("Sản phẩm không tồn tại.");
        if ($quantity > $item['stock']) throw new Exception("Sản phẩm '{$item['name']}' không đủ hàng (còn {$item['stock']}).");

        // Chuẩn hóa cấu trúc để xử lý chung
        $order_items[] = [
            'item_id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'total' => $item['price'] * $quantity
        ];
    }
    // --- TRƯỜNG HỢP 2: MUA TỪ GIỎ HÀNG ---
    else {
        $cart_ids_str = $_POST['cart_ids'] ?? '';
        if (empty($cart_ids_str)) throw new Exception("Giỏ hàng rỗng hoặc chưa chọn sản phẩm.");

        $cart_ids = array_filter(array_map('intval', explode(',', $cart_ids_str)));

        // Lấy thông tin từ giỏ hàng + items
        $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
        $types = str_repeat('i', count($cart_ids)) . 'i';
        $params = array_merge($cart_ids, [$user_id]);

        $stmt = $db->prepare("SELECT c.item_id, c.quantity, i.price, i.stock, i.name 
                              FROM cart c 
                              JOIN items i ON c.item_id = i.id 
                              WHERE c.id IN ($placeholders) AND c.user_id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($results)) throw new Exception("Không tìm thấy sản phẩm trong giỏ.");

        foreach ($results as $row) {
            if ($row['quantity'] > $row['stock']) throw new Exception("Sản phẩm '{$row['name']}' hết hàng.");
            $order_items[] = [
                'item_id' => $row['item_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'total' => $row['quantity'] * $row['price']
            ];
        }
    }

    // --- TÍNH TOÁN TỔNG TIỀN ---
    $total_amount = 0;
    foreach ($order_items as $item) {
        $total_amount += $item['total'];
    }

    // --- XỬ LÝ VOUCHER ---
    $discount_amount = 0;
    $voucher_id_used = null;
    if ($voucher_code) {
        $v_stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? AND (target_user_id IS NULL OR target_user_id = 0 OR target_user_id = ?) AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())");
        $v_stmt->bind_param("si", $voucher_code, $user_id);
        $v_stmt->execute();
        $voucher = $v_stmt->get_result()->fetch_assoc();

        if ($voucher && $voucher['quantity'] > $voucher['redeemed_count'] && $total_amount >= $voucher['min_order_amount']) {
            $discount_amount = ($voucher['discount_type'] === 'fixed') ? $voucher['discount_amount'] : ($total_amount * $voucher['discount_amount']) / 100;
            if ($voucher['max_discount'] > 0) $discount_amount = min($discount_amount, $voucher['max_discount']);
            $voucher_id_used = $voucher['id'];
        }
    }

    $final_total = max(0, ($total_amount + $shipping_fee) - $discount_amount);

    // --- TẠO ĐƠN HÀNG (ORDERS) ---
    $stmt = $db->prepare("INSERT INTO orders (user_id, order_date, total_amount, status, receiver_name, receiver_phone, shipping_address, payment_method, shipping_fee, discount_amount, voucher_code) VALUES (?, NOW(), ?, 'Đang chờ xác nhận', ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssssiis", $user_id, $final_total, $receiver_name, $receiver_phone, $shipping_address, $payment_method, $shipping_fee, $discount_amount, $voucher_code);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $order_id = $db->insert_id;

    // --- TẠO CHI TIẾT ĐƠN HÀNG & CẬP NHẬT KHO ---
    $stmt_d = $db->prepare("INSERT INTO order_details (order_id, item_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");

    // Câu lệnh update kho chuẩn (dùng cột 'stock')
    $stmt_s = $db->prepare("UPDATE items SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?");

    foreach ($order_items as $item) {
        // Lưu chi tiết
        $stmt_d->bind_param("iiid", $order_id, $item['item_id'], $item['quantity'], $item['price']);
        $stmt_d->execute();

        // Trừ kho và tăng lượt bán
        $stmt_s->bind_param("iii", $item['quantity'], $item['quantity'], $item['item_id']);
        $stmt_s->execute();
    }

    // --- XÓA GIỎ HÀNG (Chỉ thực hiện nếu mua từ giỏ) ---
    if ($action !== 'buynow' && !empty($cart_ids)) {
        $db->query("DELETE FROM cart WHERE id IN (" . implode(',', $cart_ids) . ") AND user_id = $user_id");
    }

    // --- CẬP NHẬT VOUCHER ---
    if ($voucher_id_used) {
        $db->query("UPDATE vouchers SET redeemed_count = redeemed_count + 1 WHERE id = $voucher_id_used");
        $chk = $db->query("SELECT id FROM user_vouchers WHERE user_id = $user_id AND voucher_id = $voucher_id_used AND is_used = 0 LIMIT 1");
        if ($chk->num_rows > 0) $db->query("UPDATE user_vouchers SET is_used = 1 WHERE id = " . $chk->fetch_assoc()['id']);
    }

    $db->commit();

    // [THAY ĐỔI] Lưu thông báo thành công vào Session
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Đặt hàng thành công!';
    $_SESSION['swal_text'] = 'Cảm ơn bạn đã mua sắm. Đơn hàng đang chờ xác nhận.';

    // Chuyển hướng ngay lập tức
    header('Location: ../User_Screen/History_Screen.php');
    exit();
} catch (Exception $e) {
    $db->rollback();

    // [THAY ĐỔI] Lưu thông báo lỗi vào Session
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Đặt hàng thất bại';
    $_SESSION['swal_text'] = $e->getMessage();

    // Chuyển hướng về giỏ hàng để kiểm tra lại
    header('Location: ../User_Screen/Cart_Screen.php');
    exit();
}
