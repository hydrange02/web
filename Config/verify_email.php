<?php
// File: Web php/Config/verify_email.php
session_start();
include 'Database.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($email) || empty($token)) {
    die("Link không hợp lệ.");
}

$db = Database::getInstance()->getConnection();

// Kiểm tra token khớp với email và chưa hết hạn
$stmt = $db->prepare("SELECT id, token_expire FROM users WHERE email = ? AND verification_token = ?");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Kiểm tra hết hạn
    if (strtotime($user['token_expire']) < time()) {
        die("Link xác thực đã hết hạn. Vui lòng yêu cầu gửi lại.");
    }

    // Xác thực thành công: Xóa token, set is_verified = 1
    $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expire = NULL WHERE id = ?");
    $update->bind_param("i", $user['id']);
    
    if ($update->execute()) {
        echo "
        <div style='text-align:center; padding-top: 50px; font-family: sans-serif;'>
            <h1 style='color: green;'>Xác thực thành công! ✅</h1>
            <p>Email của bạn đã được xác minh.</p>
            <a href='../index.php'>Quay về trang chủ</a>
        </div>
        ";
    } else {
        echo "Lỗi hệ thống.";
    }
} else {
    echo "Link xác thực không đúng hoặc đã được sử dụng.";
}
?>