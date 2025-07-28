<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (empty($username) || empty($password)) {
    echo json_encode(["success" => false, "message" => "ユーザー名とパスワードを入力してください"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT id, username, password, team FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['team'] = $user['team'];

        echo json_encode([
            "success" => true,
            "message" => "ログイン成功",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "team" => $user['team']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "ユーザー名またはパスワードが間違っています"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "ログインエラー: " . $e->getMessage()]);
}
?>