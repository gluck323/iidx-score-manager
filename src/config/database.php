<?php
// src/config/database.php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    public $conn;

    public function __construct() {
        // Xserver環境の設定
        $this->host = 'localhost';
        $this->db_name = 'skyponet_iidxscoremanager';
        $this->username = 'skyponet_kota';
        $this->password = 'k0o1u2t3a';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // エラーをログに記録（echoではなく）
            error_log("Database connection error: " . $exception->getMessage());
            // nullを返してAPIで適切にハンドリングさせる
            return null;
        }
        return $this->conn;
    }
}
