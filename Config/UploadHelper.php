<?php
class UploadHelper {
    // Chỉ cho phép các đuôi ảnh an toàn
    private static $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Chỉ cho phép các MIME type ảnh thực sự
    private static $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Giới hạn 5MB
    private static $max_size = 5 * 1024 * 1024; 

    /**
     * Xử lý upload file an toàn
     * @param string $fileInputName Tên của input file trong form (vd: 'avatar')
     * @param string $targetDir Thư mục lưu file (vd: '../assets/uploads/')
     */
    public static function process($fileInputName, $targetDir = '../assets/uploads/') {
        // 1. Kiểm tra xem có file được gửi lên không
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] == UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Chưa chọn file nào.'];
        }

        $file = $_FILES[$fileInputName];

        // 2. Kiểm tra lỗi hệ thống từ PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Lỗi upload hệ thống: ' . $file['error']];
        }

        // 3. Kiểm tra kích thước
        if ($file['size'] > self::$max_size) {
            return ['success' => false, 'message' => 'File quá lớn! Tối đa 5MB.'];
        }

        // 4. Lấy thông tin file
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 5. Kiểm tra Extension (Đuôi file)
        if (!in_array($fileExt, self::$allowed_ext)) {
            return ['success' => false, 'message' => 'Định dạng file không hỗ trợ. Chỉ chấp nhận JPG, PNG, GIF, WEBP.'];
        }

        // 6. Kiểm tra MIME Type thực tế (Chống giả mạo đuôi file)
        // Yêu cầu PHP extension 'fileinfo' phải được bật
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        if (!in_array($mime, self::$allowed_mime)) {
            return ['success' => false, 'message' => 'File không phải là ảnh hợp lệ.'];
        }

        // 7. Tạo tên file mới ngẫu nhiên (QUAN TRỌNG: Chống ghi đè và Shell)
        // Ví dụ: img_6578a9b1c2d3e.jpg
        $newFileName = uniqid('img_', true) . '.' . $fileExt;
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $destPath = $targetDir . $newFileName;

        // 8. Di chuyển file từ thư mục tạm sang thư mục đích
        if (move_uploaded_file($fileTmp, $destPath)) {
            // Trả về đường dẫn để lưu vào Database
            // Loại bỏ '../' để lưu đường dẫn tương đối từ thư mục gốc web
            $dbPath = str_replace('../', '', $destPath);
            return [
                'success' => true, 
                'message' => 'Upload thành công',
                'path' => $dbPath 
            ];
        }

        return ['success' => false, 'message' => 'Không thể lưu file vào máy chủ.'];
    }
}
?>