<?php
header("Content-Type: application/json");

try {
    session_start();
    require_once __DIR__ . '/Database.php'; 

    $db = Database::getInstance()->getConnection();

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $input = trim($_POST['Username'] ?? ''); 
        $password = $_POST['password'] ?? '';

        if (empty($input) || empty($password)) {
            echo json_encode(["success"=>false, "message"=>"Vui Lòng Nhập Đủ Thông Tin"]);
            exit;
        }

        $is_email = filter_var($input, FILTER_VALIDATE_EMAIL);
        
        $query = $is_email 
            ? "SELECT id, password, role, is_verified FROM users WHERE email = ?" 
            : "SELECT id, password, role, is_verified FROM users WHERE username = ?";
        
        $field_to_bind = $input;
        
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $field_to_bind);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            echo json_encode(["success" => false, "message" => "Tên đăng nhập hoặc Email không tồn tại!"]);
            exit;
        }

        $row = $result->fetch_assoc();
        
        if ($row['is_verified'] == 0) {
            echo json_encode([
                "success" => false, 
                "message" => "Tài khoản chưa được kích hoạt. Vui lòng kiểm tra Email để xác thực!"
            ]);
            exit;
        }

        $hash_password = $row['password'];

        if (password_verify($password, $hash_password)) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role']; 
            
            // --- TỐI ƯU HÓA: CACHE THÔNG TIN VÀO SESSION ---
            // 1. Lấy thông tin cơ bản
            $u_stmt = $db->prepare("SELECT username, img FROM users WHERE id = ?");
            $u_stmt->bind_param("i", $row['id']);
            $u_stmt->execute();
            $u_data = $u_stmt->get_result()->fetch_assoc();
            $_SESSION['username'] = $u_data['username'];
            $_SESSION['img'] = $u_data['img'];
            $u_stmt->close();

            // 2. Lấy số lượng giỏ hàng ban đầu
            $c_query = $db->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = " . $row['id']);
            $c_row = $c_query->fetch_assoc();
            $_SESSION['cart_count'] = intval($c_row['total'] ?? 0);
            
            echo json_encode([
                'success' => true, 
                'message' => "Đăng nhập thành công!",
                'role' => $row['role'] 
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => "Sai mật khẩu!"]);
            exit;
        }
    }
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>