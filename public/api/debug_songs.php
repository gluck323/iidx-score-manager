<?php
// デバッグ用: position_songsとscoresテーブルの楽曲名を照合
header("Content-Type: application/json; charset=utf-8");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(["error" => "データベース接続エラー"]);
    exit;
}

try {
    // position_songsの楽曲一覧
    $positionStmt = $db->prepare("SELECT DISTINCT title, difficulty FROM position_songs ORDER BY title");
    $positionStmt->execute();
    $positionSongs = $positionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // scoresの楽曲一覧
    $scoresStmt = $db->prepare("SELECT DISTINCT song_title, difficulty FROM scores ORDER BY song_title");
    $scoresStmt->execute();
    $scoresSongs = $scoresStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // playersテーブルの内容
    $playersStmt = $db->prepare("SELECT * FROM players ORDER BY id");
    $playersStmt->execute();
    $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "position_songs" => $positionSongs,
        "scores_songs" => $scoresSongs,
        "players" => $players,
        "position_count" => count($positionSongs),
        "scores_count" => count($scoresSongs),
        "players_count" => count($players)
    ]);
    
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>