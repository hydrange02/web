<?php
// KIỂM TRA XEM CLASS ĐÃ TỒN TẠI CHƯA TRƯỚC KHI ĐỊNH NGHĨA
if (!class_exists('Database')) {
    
    require_once __DIR__ . '/Env.php';

    class Database {
        private static $instance = null;
        private $connection;

        private function __construct() {
            // Lấy cấu hình từ .env hoặc dùng mặc định
            $host = Env::get('DB_HOST', 'localhost');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', '');
            $dbname = Env::get('DB_NAME', 'shop');

            // Bật báo cáo lỗi mysqli
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            try {
                $this->connection = new mysqli($host, $user, $pass, $dbname);
                $this->connection->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e) {
                error_log("Connection failed: " . $e->getMessage());
                throw new Exception("Không thể kết nối đến cơ sở dữ liệu: " . $e->getMessage());
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

if (!isset($conn)) {
    $conn = Database::getInstance()->getConnection();
}
?>
