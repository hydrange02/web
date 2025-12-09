<?php
// File: Web php/Config/redeem_point.php
session_start();
include 'Database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$voucher_id = $_POST['voucher_id'] ?? null;

if (!$user_id) { echo json_encode(['success'=>false, 'message'=>'Chưa đăng nhập']); exit; }

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    // 1. Lấy thông tin
    $u_res = $db->query("SELECT current_points FROM users WHERE id = $user_id");
    $user = $u_res->fetch_assoc();
    
    $v_stmt = $db->prepare("SELECT * FROM vouchers WHERE id = ? FOR UPDATE");
    $v_stmt->bind_param("i", $voucher_id);
    $v_stmt->execute();
    $voucher = $v_stmt->get_result()->fetch_assoc();

    if (!$voucher) throw new Exception("Voucher không tồn tại");
    if (!empty($voucher['start_date']) && strtotime($voucher['start_date']) > time()) throw new Exception("Voucher chưa phát hành");
    
    // 2. Kiểm tra điểm & số lượng
    if ($user['current_points'] < $voucher['points_cost']) throw new Exception("Bạn không đủ điểm!");
    if ($voucher['quantity'] <= $voucher['redeemed_count']) throw new Exception("Voucher đã hết!");

    // 3. Kiểm tra lịch sử đổi
    $check_hist = $db->query("SELECT COUNT(*) as qty, MAX(created_at) as last_time FROM user_vouchers WHERE user_id = $user_id AND voucher_id = $voucher_id");
    $history = $check_hist->fetch_assoc();
    $owned_count = $history['qty'];
    $last_time = $history['last_time'];

    // 3.1. Check giới hạn
    if ($owned_count >= $voucher['limit_per_user']) throw new Exception("Bạn đã đạt giới hạn đổi voucher này!");

    // 3.2. Check Cooldown (Tính theo giây)
    $cooldown_sec = intval($voucher['cooldown_seconds']);
    if ($cooldown_sec > 0 && $last_time) {
        $next_time = strtotime($last_time) + $cooldown_sec;
        if (time() < $next_time) {
            $diff = $next_time - time();
            $msg = "Vui lòng chờ ";
            if ($diff > 86400) $msg .= floor($diff/86400) . " ngày ";
            elseif ($diff > 3600) $msg .= floor($diff/3600) . " giờ ";
            else $msg .= ceil($diff/60) . " phút ";
            $msg .= "để đổi tiếp!";
            throw new Exception($msg);
        }
    }

    // 4. Trừ điểm & Ghi nhận
    $new_points = $user['current_points'] - $voucher['points_cost'];
    $db->query("UPDATE users SET current_points = $new_points WHERE id = $user_id");
    $db->query("UPDATE vouchers SET redeemed_count = redeemed_count + 1 WHERE id = $voucher_id");

    $stmt_add = $db->prepare("INSERT INTO user_vouchers (user_id, voucher_id) VALUES (?, ?)");
    $stmt_add->bind_param("ii", $user_id, $voucher_id);
    $stmt_add->execute();

    $db->commit();
    echo json_encode(['success'=>true, 'message'=>'Đổi thành công!', 'new_points'=>$new_points]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>