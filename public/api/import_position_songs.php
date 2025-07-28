<?php
// public/api/import_position_songs.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POSTメソッドのみ対応"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$songs = $input['songs'] ?? [];

if (empty($songs)) {
    echo json_encode(["success" => false, "message" => "楽曲データが空です"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(["success" => false, "message" => "データベース接続エラー"]);
    exit;
}

try {
    // テーブル作成
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS position_songs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        position_category ENUM('先鋒', '次鋒', '中堅', '副将', '大将') NOT NULL,
        song_number INT,
        title VARCHAR(255) NOT NULL,
        difficulty VARCHAR(50) NOT NULL,
        is_shinrisen BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_position (position_category),
        INDEX idx_title (title),
        INDEX idx_shinrisen (is_shinrisen)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $db->exec($createTableSQL);
    
    // 既存データ削除
    $position = $songs[0]['position'];
    $is_shinrisen = $songs[0]['is_shinrisen'] ? 1 : 0;
    
    $deleteSQL = "DELETE FROM position_songs WHERE position_category = ? AND is_shinrisen = ?";
    $deleteStmt = $db->prepare($deleteSQL);
    $deleteStmt->execute([$position, $is_shinrisen]);
    
    // データ挿入
    $insertSQL = "INSERT INTO position_songs (position_category, song_number, title, difficulty, is_shinrisen) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertSQL);
    
    $imported_count = 0;
    foreach ($songs as $song) {
        $result = $insertStmt->execute([
            $song['position'],
            $song['number'],
            $song['title'],
            $song['difficulty'],
            $song['is_shinrisen'] ? 1 : 0
        ]);
        
        if ($result) {
            $imported_count++;
        }
    }
    
    $category = $is_shinrisen ? "{$position}心理戦" : $position;
    echo json_encode([
        "success" => true,
        "message" => "{$category}: {$imported_count}曲をインポートしました"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "エラー: " . $e->getMessage()
    ]);
}
?>