<?php
// File: Web php/Config/update_info.php
session_start();
include 'Database.php'; 
include 'MailHelper.php'; // Include thêm MailHelper
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Phiên hết hạn']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Lỗi bảo mật CSRF']); exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($username)) {
         echo json_encode(['success' => false, 'message' => 'Tên không được để trống']); exit;
    }

    $db = Database::getInstance()->getConnection();

    // Lấy email cũ để so sánh
    $oldQ = $db->prepare("SELECT email FROM users WHERE id = ?");
    $oldQ->bind_param("i", $user_id);
    $oldQ->execute();
    $oldData = $oldQ->get_result()->fetch_assoc();
    $oldEmail = $oldData['email'];

    // Nếu có nhập email, kiểm tra valid và trùng lặp
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']); exit;
        }
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi người khác']); exit;
        }
    }

    // Cập nhật thông tin
    $stmt = $db->prepare("UPDATE users SET username=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param('sssi', $username, $email, $phone, $user_id);

    if ($stmt->execute()) {
        $msg = 'Cập nhật thông tin thành công!';
        
        // LOGIC GỬI MAIL XÁC THỰC KHI ĐỔI EMAIL
        if (!empty($email) && $email !== $oldEmail) {
            // Tạo token xác thực
            $token = bin2hex(random_bytes(32));
            $token_expire = date('Y-m-d H:i:s', strtotime('+1 day'));
            
            // Lưu token vào DB (Cần đảm bảo bảng users có cột verification_token)
            // Tạm thời set is_verified = 0
            $tkStmt = $db->prepare("UPDATE users SET verification_token = ?, token_expire = ?, is_verified = 0 WHERE id = ?");
            $tkStmt->bind_param("ssi", $token, $token_expire, $user_id);
            $tkStmt->execute();

            // Gửi Mail
            if(MailHelper::sendVerificationEmail($email, $token)) {
                $msg .= ' Vui lòng kiểm tra hộp thư để xác thực Email mới.';
            } else {
                $msg .= ' (Lỗi gửi mail xác thực).';
            }
        }

        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $db->error]);
    }
}
?>