<?php
// src/config/database.php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    public $conn;

    public function __construct() {
        // 環境判定：Xserverかローカル環境かを判定
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.xsrv.jp') !== false) {
            // Xserver環境の設定
            $this->host = 'mysql***.xserver.jp'; // ここにXserverのMySQLホスト名を入力
            $this->db_name = 'your_database_name'; // ここにXserverのデータベース名を入力
            $this->username = 'your_username'; // ここにXserverのユーザー名を入力
            $this->password = 'your_password'; // ここにXserverのパスワードを入力
        } else {
            // ローカル環境（XAMPP）の設定
            $this->host = 'localhost';
            $this->db_name = 'iidx_score_manager';
            $this->username = 'root';
            $this->password = '';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
