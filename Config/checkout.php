<?php
// File: Web php/Config/checkout.php
session_start();
include 'Database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header('Location: ../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) { die("Lỗi bảo mật."); }

$cart_ids_str = $_POST['cart_ids'] ?? '';
$receiver_name = trim($_POST['receiver_name'] ?? '');
$receiver_phone = trim($_POST['receiver_phone'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'COD';
$voucher_code = trim($_POST['voucher_code'] ?? '');
$shipping_fee = intval($_POST['shipping_fee'] ?? 0);

if (empty($cart_ids_str) || empty($receiver_name) || empty($receiver_phone) || empty($shipping_address)) { echo "<script>alert('Thiếu thông tin!'); history.back();</script>"; exit; }

$db = Database::getInstance()->getConnection();
$cart_ids = array_filter(array_map('intval', explode(',', $cart_ids_str)));

try {
    $db->begin_transaction();

    // Lấy giỏ hàng
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $types = str_repeat('i', count($cart_ids)) . 'i';
    $params = array_merge($cart_ids, [$user_id]);
    $stmt = $db->prepare("SELECT c.id, c.item_id, c.quantity, i.price, i.stock, i.name FROM cart c JOIN items i ON c.item_id = i.id WHERE c.id IN ($placeholders) AND c.user_id = ?");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($cart_items)) throw new Exception("Giỏ hàng rỗng.");
    
    $total_amount = 0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) throw new Exception("Sản phẩm {$item['name']} hết hàng.");
        $total_amount += $item['quantity'] * $item['price'];
    }

    // Voucher
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
    
    // Tạo đơn
    $stmt = $db->prepare("INSERT INTO orders (user_id, order_date, total_amount, status, receiver_name, receiver_phone, shipping_address, payment_method, shipping_fee, discount_amount, voucher_code) VALUES (?, NOW(), ?, 'Đang chờ xác nhận', ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssssiis", $user_id, $final_total, $receiver_name, $receiver_phone, $shipping_address, $payment_method, $shipping_fee, $discount_amount, $voucher_code);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $order_id = $db->insert_id;

    // Chi tiết & Kho
    $stmt_d = $db->prepare("INSERT INTO order_details (order_id, item_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
    $stmt_s = $db->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
    foreach ($cart_items as $item) {
        $stmt_d->bind_param("iiid", $order_id, $item['item_id'], $item['quantity'], $item['price']);
        $stmt_d->execute();
        $stmt_s->bind_param("ii", $item['quantity'], $item['item_id']);
        $stmt_s->execute();
    }

    // Xóa giỏ
    $db->query("DELETE FROM cart WHERE id IN (" . implode(',', $cart_ids) . ") AND user_id = $user_id");

    // Update Voucher
    if ($voucher_id_used) {
        $db->query("UPDATE vouchers SET redeemed_count = redeemed_count + 1 WHERE id = $voucher_id_used");
        $chk = $db->query("SELECT id FROM user_vouchers WHERE user_id = $user_id AND voucher_id = $voucher_id_used AND is_used = 0 LIMIT 1");
        if ($chk->num_rows > 0) $db->query("UPDATE user_vouchers SET is_used = 1 WHERE id = " . $chk->fetch_assoc()['id']);
    }

    $db->commit();
    echo "<script>alert('Đặt hàng thành công!'); window.location.href = '../User_Screen/History_Screen.php';</script>";

} catch (Exception $e) {
    $db->rollback();
    echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.location.href = '../User_Screen/Cart_Screen.php';</script>";
}
?>