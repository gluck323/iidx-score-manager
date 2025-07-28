<?php
// public/api/scores.php (デバッグ版)
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $song_title = $_GET['song_title'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $include_average = isset($_GET['include_average']) ? intval($_GET['include_average']) : 0;
        
        if ($song_title) {
            try {
                // include_averageパラメータによってアベレージスコアの表示を制御
                $averageCondition = $include_average ? '' : 'AND s.is_average_mode = 0';
                
                if ($difficulty) {
                    // 難易度指定がある場合、その難易度のスコアのみ取得
                    $stmt = $db->prepare("
                        SELECT s.*, p.name as player_name, p.is_opponent 
                        FROM scores s 
                        JOIN players p ON s.player_id = p.id 
                        WHERE s.song_title = ? AND s.difficulty = ? {$averageCondition}
                        ORDER BY s.score DESC, p.name ASC
                    ");
                    $stmt->execute([$song_title, $difficulty]);
                } else {
                    // 難易度指定がない場合、全ての難易度のスコアを取得（既存の動作を維持）
                    $stmt = $db->prepare("
                        SELECT s.*, p.name as player_name, p.is_opponent 
                        FROM scores s 
                        JOIN players p ON s.player_id = p.id 
                        WHERE s.song_title = ? {$averageCondition}
                        ORDER BY s.score DESC, p.name ASC
                    ");
                    $stmt->execute([$song_title]);
                }
                
                $scores = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $scores[] = $row;
                }
                echo json_encode($scores);
            } catch (Exception $e) {
                echo json_encode(["error" => $e->getMessage()]);
            }
        } else {
            echo json_encode(["message" => "楽曲名が必要です"]);
        }
        break;
        
    case 'POST':
    case 'PUT':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false, "message" => "ログインが必要です"]);
            exit;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        
        try {
            // プレイヤーテーブル作成
            $createPlayersSQL = "
            CREATE TABLE IF NOT EXISTS players (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                team ENUM('KBM', 'BBD') NOT NULL,
                is_opponent BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $db->exec($createPlayersSQL);
            
            // デバッグ情報
            $debugInfo = [
                'session_user_id' => $_SESSION['user_id'],
                'session_username' => $_SESSION['username'],
                'input_player_id' => $input['player_id'],
                'input_song_id' => $input['song_id']
            ];
            
            // プレイヤーID決定ロジック
            $originalPlayerId = intval($input['player_id']);
            $isCurrentUser = false;
            
            // 現在のログインユーザーかどうかを判定
            if ($originalPlayerId == 99 || $originalPlayerId >= 1000) {
                $isCurrentUser = true;
                $playerId = 1000 + $_SESSION['user_id'];
                $playerName = $_SESSION['username'];
                $isOpponent = false;
                $team = 'KBM';
            } else {
                // BBDの対戦相手
                $playerNames = [
                    1 => 'ゆうすけ',
                    2 => 'イリス', 
                    3 => 'ゆーと',
                    4 => 'まぐ',
                    5 => 'レクリア',
                    6 => 'みゆ'
                ];
                $playerId = $originalPlayerId;
                $playerName = $playerNames[$playerId] ?? 'Unknown';
                $isOpponent = true;
                $team = 'BBD';
            }
            
            $debugInfo['calculated_player_id'] = $playerId;
            $debugInfo['calculated_player_name'] = $playerName;
            $debugInfo['is_current_user'] = $isCurrentUser;
            
            // プレイヤーレコードの作成/更新
            $stmt = $db->prepare("INSERT INTO players (id, name, team, is_opponent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            $stmt->execute([$playerId, $playerName, $team, $isOpponent]);

            // スコアテーブル作成（UNIQUE制約削除）
            $createScoresSQL = "
            CREATE TABLE IF NOT EXISTS scores (
                id INT PRIMARY KEY AUTO_INCREMENT,
                song_title VARCHAR(255) NOT NULL,
                player_id INT NOT NULL,
                difficulty VARCHAR(50) NOT NULL,
                score INT,
                clear_lamp VARCHAR(10) DEFAULT 'F',
                miss_count INT DEFAULT 0,
                recommended_options TEXT,
                notes TEXT,
                position_category ENUM('先鋒', '次鋒', '中堅', '副将', '大将') NOT NULL,
                is_shinrisen BOOLEAN DEFAULT FALSE,
                is_average_mode BOOLEAN DEFAULT FALSE,
                updated_by VARCHAR(100),
                updated_by_user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
                FOREIGN KEY (updated_by_user_id) REFERENCES users(id),
                INDEX idx_song_player (song_title, player_id),
                INDEX idx_position (position_category)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $db->exec($createScoresSQL);

            // 既存スコアの確認
            $checkStmt = $db->prepare("
                SELECT id FROM scores 
                WHERE song_title = ? AND player_id = ? AND difficulty = ? AND is_average_mode = ?
            ");
            $checkStmt->execute([
                $input['song_id'],
                $playerId,
                $input['difficulty'],
                $input['is_average_mode'] ? 1 : 0
            ]);
            $existingScore = $checkStmt->fetch();

            if ($existingScore) {
                // 既存スコアがある場合は更新
                $stmt = $db->prepare("
                    UPDATE scores SET 
                    score = ?, clear_lamp = ?, miss_count = ?, 
                    recommended_options = ?, notes = ?, position_category = ?, 
                    is_shinrisen = ?, updated_by = ?, updated_by_user_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $input['score'],
                    $input['clear_lamp'],
                    $input['miss_count'],
                    $input['recommended_options'],
                    $input['notes'],
                    $input['position_category'],
                    $input['is_shinrisen'] ? 1 : 0,
                    $_SESSION['username'],
                    $_SESSION['user_id'],
                    $existingScore['id']
                ]);
                $actionMessage = "スコアが更新されました";
            } else {
                // 既存スコアがない場合は新規登録
                $stmt = $db->prepare("
                    INSERT INTO scores 
                    (song_title, player_id, difficulty, score, clear_lamp, miss_count, 
                     recommended_options, notes, position_category, is_shinrisen, is_average_mode, updated_by, updated_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $input['song_id'],
                    $playerId,
                    $input['difficulty'],
                    $input['score'],
                    $input['clear_lamp'],
                    $input['miss_count'],
                    $input['recommended_options'],
                    $input['notes'],
                    $input['position_category'],
                    $input['is_shinrisen'] ? 1 : 0,
                    $input['is_average_mode'] ? 1 : 0,
                    $_SESSION['username'],
                    $_SESSION['user_id']
                ]);
                $actionMessage = "スコアが登録されました";
            }

            if ($result) {
                echo json_encode([
                    "success" => true, 
                    "message" => $actionMessage,
                    "debug" => $debugInfo
                ]);
            } else {
                echo json_encode([
                    "success" => false, 
                    "message" => "処理に失敗しました",
                    "debug" => $debugInfo
                ]);
            }

        } catch (Exception $e) {
            echo json_encode([
                "success" => false, 
                "message" => "エラー: " . $e->getMessage(),
                "debug" => $debugInfo ?? []
            ]);
        }
        break;
        
    case 'DELETE':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false, "message" => "ログインが必要です"]);
            exit;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        
        try {
            $songTitle = $input['song_title'] ?? '';
            $playerId = intval($input['player_id']);
            $difficulty = $input['difficulty'] ?? '';
            $clearAverage = $input['clear_average'] ?? false;
            
            if (empty($songTitle) || !$playerId) {
                echo json_encode(["success" => false, "message" => "楽曲名またはプレイヤーIDが必要です"]);
                exit;
            }
            
            // 権限チェック：ログインユーザーのスコアのみ削除可能
            $currentUserPlayerId = 1000 + $_SESSION['user_id'];
            if ($playerId !== $currentUserPlayerId) {
                echo json_encode(["success" => false, "message" => "自分のスコアのみ削除できます"]);
                exit;
            }
            
            if ($clearAverage) {
                if ($difficulty) {
                    $stmt = $db->prepare("DELETE FROM scores WHERE song_title = ? AND player_id = ? AND difficulty = ? AND is_average_mode = 1");
                    $result = $stmt->execute([$songTitle, $playerId, $difficulty]);
                } else {
                    $stmt = $db->prepare("DELETE FROM scores WHERE song_title = ? AND player_id = ? AND is_average_mode = 1");
                    $result = $stmt->execute([$songTitle, $playerId]);
                }
                $message = "アベレージスコアが削除されました";
            } else {
                if ($difficulty) {
                    $stmt = $db->prepare("DELETE FROM scores WHERE song_title = ? AND player_id = ? AND difficulty = ? AND is_average_mode = 0");
                    $result = $stmt->execute([$songTitle, $playerId, $difficulty]);
                } else {
                    $stmt = $db->prepare("DELETE FROM scores WHERE song_title = ? AND player_id = ? AND is_average_mode = 0");
                    $result = $stmt->execute([$songTitle, $playerId]);
                }
                $message = "スコアが削除されました";
            }

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => $message]);
            } else {
                echo json_encode(["success" => false, "message" => "削除するスコアが見つかりません"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "削除エラー: " . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(["message" => "サポートされていないメソッドです"]);
        break;
}
?>