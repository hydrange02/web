<?php
// File: Web php/Config/check_voucher.php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$code = $_POST['code'] ?? '';
$total_order = $_POST['total_order'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (empty($code)) { echo json_encode(['success' => false, 'message' => 'Chưa nhập mã']); exit; }

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? 
    AND (target_user_id IS NULL OR target_user_id = 0 OR target_user_id = ?) 
    AND (start_date IS NOT NULL AND start_date <= CURDATE()) 
    AND (end_date IS NULL OR end_date >= CURDATE())");

$stmt->bind_param("si", $code, $user_id); 
$stmt->execute();
$res = $stmt->get_result();
$voucher = $res->fetch_assoc();

if (!$voucher) { echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ hoặc chưa phát hành!']); exit; }

if ($voucher['quantity'] <= $voucher['redeemed_count']) { echo json_encode(['success' => false, 'message' => 'Mã đã hết lượt sử dụng!']); exit; }

if ($total_order < $voucher['min_order_amount']) { echo json_encode(['success' => false, 'message' => 'Đơn hàng phải từ ' . number_format($voucher['min_order_amount']) . 'đ']); exit; }

$discount = ($voucher['discount_type'] === 'fixed') ? $voucher['discount_amount'] : ($total_order * $voucher['discount_amount']) / 100;
if ($voucher['max_discount'] > 0 && $discount > $voucher['max_discount']) $discount = $voucher['max_discount'];
if ($discount > $total_order) $discount = $total_order;

echo json_encode(['success' => true, 'message' => 'Áp dụng thành công!', 'discount' => floor($discount)]);
?>