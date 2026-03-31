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