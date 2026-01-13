<?php
class Env {
    // Hàm nạp biến môi trường từ file .env
    public static function load($path) {
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Bỏ qua dòng comment bắt đầu bằng #
            if (strpos(trim($line), '#') === 0) continue; 

            // Tách key và value
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Chỉ nạp nếu chưa tồn tại (tránh ghi đè biến server thực)
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    // --- BỔ SUNG HÀM GET ĐỂ KHẮC PHỤC LỖI ---
    public static function get($key, $default = null) {
        // 1. Kiểm tra trong $_ENV (ưu tiên cao nhất do load() nạp vào đây)
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        // 2. Kiểm tra trong $_SERVER
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        // 3. Kiểm tra bằng getenv() (biến môi trường hệ thống)
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        // 4. Nếu không tìm thấy thì trả về giá trị mặc định
        return $default;
    }
}

// Tự động load file .env ngay khi file này được include
// __DIR__ đang là thư mục "Config", đi ra 1 cấp ".." sẽ gặp file ".env"
Env::load(__DIR__ . '/../.env'); 
?>