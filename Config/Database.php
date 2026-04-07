<?php
// KIỂM TRA XEM CLASS ĐÃ TỒN TẠI CHƯA TRƯỚC KHI ĐỊNH NGHĨA
if (!class_exists('Database')) {
    
    // Nạp file Env.php để lấy cấu hình
    require_once __DIR__ . '/Env.php';

    class Database {
        private static $instance = null;
        private $connection;

        private function __construct() {
            // 1. Lấy cấu hình từ .env hoặc dùng mặc định từ Aiven
            $host = Env::get('DB_HOST');
            $user = Env::get('DB_USER');
            $pass = Env::get('DB_PASS'); // Điền password Aiven vào .env
            $dbname = Env::get('DB_NAME');
            $port = Env::get('DB_PORT');

            $base_path = dirname(__DIR__);
            $ssl_ca = $base_path . '/ca.pem';

            // Kiểm tra xem file có thực sự tồn tại không trước khi dùng
            if (!file_exists($ssl_ca)) {
                // Nếu vẫn không thấy, thử dùng DOCUMENT_ROOT của Apache
                $ssl_ca = $_SERVER['DOCUMENT_ROOT'] . '/ca.pem';
            }

            // Nếu đến đây vẫn không thấy thì báo lỗi chi tiết để debug
            if (!file_exists($ssl_ca)) {
                throw new Exception("Lỗi: Không tìm thấy file ca.pem tại đường dẫn: " . $ssl_ca);
            }
            // Bật báo cáo lỗi mysqli
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            try {
                // 3. Khởi tạo mysqli
                $this->connection = mysqli_init();

                // 4. Thiết lập SSL
                mysqli_ssl_set($this->connection, NULL, NULL, $ssl_ca, NULL, NULL);

                // 5. Kết nối thực tế
                $success = mysqli_real_connect(
                    $this->connection, 
                    $host, 
                    $user, 
                    $pass, 
                    $dbname, 
                    $port, 
                    NULL, 
                    MYSQLI_CLIENT_SSL
                );

                if (!$success) {
                    throw new Exception("mysqli_real_connect failed");
                }

                $this->connection->set_charset("utf8mb4");

            } catch (Exception $e) {
                error_log("Connection failed: " . $e->getMessage());
                throw new Exception("Lỗi kết nối database: " . $e->getMessage());
            }
        }

        public static function getInstance() {
            if (!self::$instance) {
                self::$instance = new Database();
            }
            return self::$instance;
        }

        public function getConnection() {
            return $this->connection;
        }
    }
}

// Khởi tạo biến $conn dùng chung
if (!isset($conn)) {
    $conn = Database::getInstance()->getConnection();
}
?>  