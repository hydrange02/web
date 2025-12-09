<?php
// KIỂM TRA XEM CLASS ĐÃ TỒN TẠI CHƯA TRƯỚC KHI ĐỊNH NGHĨA
if (!class_exists('Database')) {

    class Database {
        private static $instance = null;
        private $connection;
        private $host = "localhost";
        private $user = "root";
        private $password = "";
        private $database = "shop";

        private function __construct() {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->connection = new mysqli($this->host, $this->user, $this->password, $this->database);
            $this->connection->set_charset("utf8mb4");

            if ($this->connection->connect_error) {
                error_log("Connection failed: " . $this->connection->connect_error);
                die("Lỗi kết nối cơ sở dữ liệu.");
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

// Chỉ khởi tạo $conn nếu chưa có
if (!isset($conn)) {
    $conn = Database::getInstance()->getConnection();
}
?>