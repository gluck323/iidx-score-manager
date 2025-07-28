<?php
// public/api/register.php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

$input = json_decode(file_get_contents("php://input"), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$team = $input['team'] ?? '';

if (empty($username) || empty($password) || empty($team)) {
    echo json_encode(["success" => false, "message" => "必須項目を入力してください"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // テーブル作成
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(100) NOT NULL,
        team ENUM('KBM', 'BBD') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $db->exec($createTableSQL);

    // 重複チェック
    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);
    if ($checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "ユーザー名が既に存在します"]);
        exit;
    }

    // ユーザー登録
    $insertStmt = $db->prepare("INSERT INTO users (username, password, team) VALUES (?, ?, ?)");
    $insertStmt->execute([$username, $password, $team]);

    echo json_encode(["success" => true, "message" => "ユーザー登録が完了しました"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "登録エラー: " . $e->getMessage()]);
}