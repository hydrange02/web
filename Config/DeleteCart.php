<?php
session_start();
// Đảm bảo đường dẫn include .php là chính xác
include 'Database.php'; 

// Lấy ID người dùng từ session
$user_id = $_SESSION['user_id'] ?? null;

// Lấy ID của mục giỏ hàng cần xóa từ URL (DeleteCart.php?id=...)
$cart_id = $_GET['id'] ?? null;

// 1. Kiểm tra đăng nhập và ID mục giỏ hàng
if (!$user_id) {
    // Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
    header("Location: ../login.php"); 
    exit;
}

if (!$cart_id || !is_numeric($cart_id)) {
    // Nếu không có ID hợp lệ, chuyển hướng về trang giỏ hàng
    header("Location: ../Cart_Screen.php"); 
    exit;
}

// 2. Chuẩn bị và thực thi truy vấn xóa
// ĐIỀU QUAN TRỌNG: Phải có user_id VÀ cart_id để đảm bảo quyền sở hữu
$sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
// Gắn tham số: cart_id (i), user_id (i)
$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute()) {
    // Kiểm tra xem có hàng nào bị ảnh hưởng không
    if ($stmt->affected_rows > 0) {
        // Xóa thành công
        $message = "Đã xóa sản phẩm khỏi giỏ hàng.";
    } else {
        // Nếu affected_rows = 0, có thể mục đó không tồn tại hoặc không thuộc về user này
        $message = "Không tìm thấy sản phẩm này trong giỏ hàng của bạn.";
    }
} else {
    $message = "Lỗi khi xóa sản phẩm: " . $stmt->error;
}

$stmt->close();
$conn->close();

// 3. Hiển thị thông báo và chuyển hướng về trang giỏ hàng
// Có thể dùng SweetAlert2 để thông báo đẹp hơn, nhưng ta dùng cách đơn giản trước
echo "<script>
    alert('$message');
    window.location.href = '../Cart_Screen.php';
</script>";

exit;

?>