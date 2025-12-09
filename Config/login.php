<?php
// File: Web php/Config/login.php
session_start();
include './Database.php'; 

header("Content-type: application/json");

$db = Database::getInstance()->getConnection();

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $input = trim($_POST['Username'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (empty($input) || empty($password)) {
        echo json_encode(["success"=>false, "message"=>"Vui Lòng Nhập Đủ Thông Tin"]);
        exit;
    }

    $is_email = filter_var($input, FILTER_VALIDATE_EMAIL);
    
    // 1. SỬA CÂU QUERY: Lấy thêm cột 'is_verified'
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
    
    // 2. THÊM ĐOẠN KIỂM TRA XÁC THỰC EMAIL
    // Nếu is_verified == 0 thì báo lỗi và dừng lại
    if ($row['is_verified'] == 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Tài khoản chưa được kích hoạt. Vui lòng kiểm tra Email để xác thực!"
        ]);
        exit;
    }

    $hash_password = $row['password'];

    if (password_verify($password, $hash_password)) {
        // Lưu thông tin user và role vào Session
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
?>