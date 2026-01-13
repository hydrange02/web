<?php
session_start();
include 'Database.php'; // Đảm bảo đường dẫn file Database chính xác

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if ($id && $user_id) {
    // Xóa sản phẩm dựa trên ID và User ID (để bảo mật)
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        // [QUAN TRỌNG] Tạo Session thông báo thành công
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Đã xóa!';
        $_SESSION['swal_text'] = 'Sản phẩm đã được loại bỏ khỏi giỏ hàng.';
    } else {
        // Tạo Session thông báo lỗi
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Lỗi!';
        $_SESSION['swal_text'] = 'Không thể xóa sản phẩm này.';
    }
    $stmt->close();
}

$conn->close();

// [QUAN TRỌNG] Chuyển hướng ngay lập tức về trang giỏ hàng
header("Location: ../User_Screen/Cart_Screen.php");
exit();
?>