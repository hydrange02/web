<?php
require_once __DIR__ . '/Env.php';

class CloudinaryHelper {
    
    public static function uploadImage($fileTmpPath) {
        // 1. Lấy cấu hình từ .env
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return ['success' => false, 'message' => 'Chưa cấu hình Cloudinary trong file .env'];
        }

        // 2. Chuẩn bị tham số để ký (Signature) - Bảo mật bắt buộc
        $timestamp = time();
        $paramsToSign = "timestamp=" . $timestamp . $apiSecret;
        $signature = sha1($paramsToSign);

        // 3. Chuẩn bị dữ liệu gửi đi (Multipart Form)
        $postFields = [
            'file' => new CURLFile($fileTmpPath), // File ảnh từ form
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            // 'folder' => 'hydrange_shop_avatars', // (Tuỳ chọn) Gom ảnh vào thư mục trên Cloudinary
        ];

        // 4. Gửi Request bằng cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloudName/image/upload");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Tắt kiểm tra SSL (chỉ dùng ở localhost nếu gặp lỗi SSL, lên host thật nên bật lại)
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 5. Xử lý kết quả trả về
        if ($curlError) {
            return ['success' => false, 'message' => 'Lỗi kết nối Cloudinary: ' . $curlError];
        }

        $data = json_decode($response, true);

        if (isset($data['secure_url'])) {
            // Thành công! Trả về link ảnh https
            return ['success' => true, 'url' => $data['secure_url']];
        } else {
            // Lỗi từ phía Cloudinary trả về
            $errorMsg = $data['error']['message'] ?? 'Lỗi không xác định';
            return ['success' => false, 'message' => 'Cloudinary Error: ' . $errorMsg];
        }
    }
}
?>