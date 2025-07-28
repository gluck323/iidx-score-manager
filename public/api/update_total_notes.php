<?php
// public/api/update_total_notes.php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POSTメソッドのみ対応"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $songTitle = $input['song_title'] ?? '';
    $difficulty = $input['difficulty'] ?? '';
    $totalNotes = intval($input['total_notes'] ?? 0);
    
    if (empty($songTitle) || empty($difficulty) || $totalNotes <= 0) {
        echo json_encode(["success" => false, "message" => "必要な項目が入力されていません"]);
        exit;
    }
    
    if ($totalNotes > 9999) {
        echo json_encode(["success" => false, "message" => "総ノーツ数は9999以下で入力してください"]);
        exit;
    }
    
    // 楽曲・難易度の組み合わせが存在するかチェック
    $checkStmt = $db->prepare("
        SELECT id FROM position_songs 
        WHERE title = ? AND difficulty = ?
        LIMIT 1
    ");
    $checkStmt->execute([$songTitle, $difficulty]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "指定された楽曲・難易度の組み合わせが見つかりません"]);
        exit;
    }
    
    // 総ノーツ数を更新
    $updateStmt = $db->prepare("
        UPDATE position_songs 
        SET total_notes = ?, 
            updated_at = NOW()
        WHERE title = ? AND difficulty = ?
    ");
    
    $result = $updateStmt->execute([$totalNotes, $songTitle, $difficulty]);
    
    if ($result) {
        // 更新ログを記録（任意）
        $logStmt = $db->prepare("
            INSERT INTO total_notes_log (song_title, difficulty, total_notes, updated_by, updated_by_user_id, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            total_notes = VALUES(total_notes),
            updated_by = VALUES(updated_by),
            updated_by_user_id = VALUES(updated_by_user_id),
            updated_at = VALUES(updated_at)
        ");
        
        // ログテーブルが存在しない場合はエラーを無視
        try {
            $logStmt->execute([$songTitle, $difficulty, $totalNotes, $_SESSION['username'], $_SESSION['user_id']]);
        } catch (Exception $e) {
            // ログテーブルがない場合は無視
        }
        
        echo json_encode([
            "success" => true, 
            "message" => "総ノーツ数を更新しました",
            "song_title" => $songTitle,
            "difficulty" => $difficulty,
            "total_notes" => $totalNotes,
            "updated_by" => $_SESSION['username']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "データベース更新に失敗しました"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "エラー: " . $e->getMessage()
    ]);
}
?>