<?php
class Database {
    private $host = "127.0.0.1"; // Use IP to avoid socket/name resolution issues
    private $port = 3306;
    private $db_name = "motorbike_ride_sharing";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->db_name);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            // Surface a clear error and stop to avoid null usage downstream
            http_response_code(500);
            echo "Connection error: " . htmlspecialchars($exception->getMessage());
            exit();
        }
        return $this->conn;
    }
}
?>