<?php
// public/api/favorites.php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// データベース接続チェック
if (!$db) {
    echo json_encode(["success" => false, "message" => "データベース接続エラーが発生しました"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // favoritesテーブルを作成（存在しない場合）
    $createFavoritesSQL = "
    CREATE TABLE IF NOT EXISTS favorites (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        song_title VARCHAR(255) NOT NULL,
        difficulty VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (user_id, song_title, difficulty),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_song (user_id, song_title, difficulty)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $db->exec($createFavoritesSQL);

    switch($method) {
        case 'GET':
            // ユーザーのお気に入り一覧を取得
            $stmt = $db->prepare("
                SELECT song_title, difficulty 
                FROM favorites 
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            $favorites = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $favorites[] = $row;
            }
            
            echo json_encode([
                "success" => true,
                "favorites" => $favorites
            ]);
            break;
            
        case 'POST':
            // お気に入りに追加
            $input = json_decode(file_get_contents("php://input"), true);
            $songTitle = $input['song_title'] ?? '';
            $difficulty = $input['difficulty'] ?? '';
            
            if (empty($songTitle) || empty($difficulty)) {
                echo json_encode(["success" => false, "message" => "楽曲名と難易度が必要です"]);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT IGNORE INTO favorites (user_id, song_title, difficulty)
                VALUES (?, ?, ?)
            ");
            $result = $stmt->execute([$_SESSION['user_id'], $songTitle, $difficulty]);
            
            if ($result) {
                echo json_encode([
                    "success" => true,
                    "message" => "お気に入りに追加しました",
                    "is_favorite" => true
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "追加に失敗しました"]);
            }
            break;
            
        case 'DELETE':
            // お気に入りから削除
            $input = json_decode(file_get_contents("php://input"), true);
            $songTitle = $input['song_title'] ?? '';
            $difficulty = $input['difficulty'] ?? '';
            
            if (empty($songTitle) || empty($difficulty)) {
                echo json_encode(["success" => false, "message" => "楽曲名と難易度が必要です"]);
                exit;
            }
            
            $stmt = $db->prepare("
                DELETE FROM favorites 
                WHERE user_id = ? AND song_title = ? AND difficulty = ?
            ");
            $result = $stmt->execute([$_SESSION['user_id'], $songTitle, $difficulty]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode([
                    "success" => true,
                    "message" => "お気に入りから削除しました",
                    "is_favorite" => false
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "削除に失敗しました"]);
            }
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "サポートされていないメソッドです"]);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "エラー: " . $e->getMessage()
    ]);
}
?>