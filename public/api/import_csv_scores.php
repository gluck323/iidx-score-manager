<?php
// public/api/import_csv_scores.php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../src/config/database.php';

// IIDXクリアタイプをシステム用に変換
function convertClearType($iidxClearType) {
    $clearTypeMap = [
        'NO PLAY' => 'F',
        'FAILED' => 'F',
        'ASSIST CLEAR' => 'AC',
        'EASY CLEAR' => 'EC',
        'CLEAR' => 'NC',
        'HARD CLEAR' => 'HC',
        'EX HARD CLEAR' => 'EXH',
        'FULLCOMBO CLEAR' => 'FC'
    ];
    
    return isset($clearTypeMap[$iidxClearType]) ? $clearTypeMap[$iidxClearType] : 'NC';
}


if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POSTメソッドのみ対応"]);
    exit;
}

if (!isset($_FILES['csvFile'])) {
    echo json_encode(["success" => false, "message" => "CSVファイルがアップロードされていません"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $csvFile = $_FILES['csvFile'];
    
    if ($csvFile['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "ファイルアップロードエラー"]);
        exit;
    }

    // CSVファイルを読み込み
    $csvData = file_get_contents($csvFile['tmp_name']);
    if ($csvData === false) {
        echo json_encode(["success" => false, "message" => "ファイル読み込みエラー"]);
        exit;
    }

    // Shift_JIS から UTF-8 に変換（IIDXのCSVはShift_JISの場合があるため）
    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'auto');
    
    // CSVパース
    $lines = explode("\n", $csvData);
    $header = str_getcsv(array_shift($lines));
    
    // IIDX公式CSVの列インデックスを取得
    $titleIndex = array_search('タイトル', $header);
    $versionIndex = array_search('バージョン', $header);
    $playCountIndex = array_search('プレー回数', $header);
    
    if ($titleIndex === false) {
        echo json_encode(["success" => false, "message" => "CSVフォーマットが正しくありません（タイトル列が見つかりません）"]);
        exit;
    }
    
    // 各難易度の列インデックスを取得
    $difficulties = ['BEGINNER', 'NORMAL', 'HYPER', 'ANOTHER', 'LEGGENDARIA'];
    $scoreColumns = [];
    
    foreach ($difficulties as $diff) {
        $scoreColumns[$diff] = [
            'difficulty' => array_search($diff . ' 難易度', $header),
            'score' => array_search($diff . ' スコア', $header),
            'pgreat' => array_search($diff . ' PGreat', $header),
            'great' => array_search($diff . ' Great', $header),
            'miss' => array_search($diff . ' ミスカン', $header),
            'clear' => array_search($diff . ' クリアタイプ', $header),
            'dj_level' => array_search($diff . ' DJ LEVEL', $header)
        ];
    }

    // プレイヤー情報取得
    $currentUserPlayerId = 1000 + $_SESSION['user_id'];
    $playerName = $_SESSION['username'];
    
    // プレイヤーが存在しない場合は作成
    $checkPlayer = $db->prepare("SELECT id FROM players WHERE id = ?");
    $checkPlayer->execute([$currentUserPlayerId]);
    
    if (!$checkPlayer->fetch()) {
        $insertPlayer = $db->prepare("INSERT INTO players (id, name, team, is_opponent) VALUES (?, ?, ?, ?)");
        $insertPlayer->execute([$currentUserPlayerId, $playerName, 'KBM', false]);
    }

    // 登録されている楽曲一覧を取得
    $songsStmt = $db->prepare("SELECT DISTINCT title FROM position_songs");
    $songsStmt->execute();
    $registeredSongs = [];
    while ($row = $songsStmt->fetch()) {
        $registeredSongs[] = $row['title'];
    }

    $processed = 0;
    $imported = 0;
    $updated = 0;
    $skipped = 0;

    // CSVの各行を処理
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = str_getcsv($line);
        if (count($data) <= $titleIndex) continue;
        
        $songTitle = trim($data[$titleIndex]);
        if (empty($songTitle)) continue;
        
        $processed++;
        
        // 楽曲名マッチング（完全一致、部分一致、類似度）
        $matchedSong = null;
        
        // 完全一致
        if (in_array($songTitle, $registeredSongs)) {
            $matchedSong = $songTitle;
        } else {
            // より柔軟なマッチング処理
            $bestMatch = null;
            $bestSimilarity = 0;
            
            foreach ($registeredSongs as $regSong) {
                // 前後の空白・記号を除去して比較
                $cleanCsvTitle = preg_replace('/[^\p{L}\p{N}]/u', '', $songTitle);
                $cleanRegTitle = preg_replace('/[^\p{L}\p{N}]/u', '', $regSong);
                
                // 完全一致（記号除去後）
                if (strtolower($cleanCsvTitle) === strtolower($cleanRegTitle)) {
                    $matchedSong = $regSong;
                    break;
                }
                
                // 部分一致チェック
                if (strpos(strtolower($regSong), strtolower($songTitle)) !== false || 
                    strpos(strtolower($songTitle), strtolower($regSong)) !== false) {
                    $matchedSong = $regSong;
                    break;
                }
                
                // 類似度チェック
                $similarity = 0;
                similar_text(strtolower($cleanCsvTitle), strtolower($cleanRegTitle), $similarity);
                if ($similarity > $bestSimilarity && $similarity >= 75) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $regSong;
                }
            }
            
            // 最も類似度の高いものを採用
            if (!$matchedSong && $bestMatch) {
                $matchedSong = $bestMatch;
            }
        }
        
        if (!$matchedSong) {
            $skipped++;
            continue;
        }

        // 各難易度のスコアを処理
        foreach ($difficulties as $difficulty) {
            $cols = $scoreColumns[$difficulty];
            
            // 必要な列が存在するかチェック
            if ($cols['score'] === false || !isset($data[$cols['score']])) continue;
            
            $score = intval($data[$cols['score']]);
            $clearType = isset($data[$cols['clear']]) ? trim($data[$cols['clear']]) : '';
            
            // NO PLAYやスコア0の場合はスキップ
            if ($score <= 0 || $clearType === 'NO PLAY') continue;
            
            $missCount = 0;
            if ($cols['miss'] !== false && isset($data[$cols['miss']]) && is_numeric($data[$cols['miss']])) {
                $missCount = intval($data[$cols['miss']]);
            }
            
            
            // クリアタイプの変換（IIDX形式 → システム形式）
            $clearLamp = convertClearType($clearType);
            
            // 難易度レベルを取得（必要に応じて）
            $difficultyLevel = '';
            if ($cols['difficulty'] !== false && isset($data[$cols['difficulty']])) {
                $difficultyLevel = trim($data[$cols['difficulty']]);
            }
            
            // position_songsテーブルから該当する楽曲・難易度の組み合わせを取得
            $positionStmt = $db->prepare("
                SELECT position_category, is_shinrisen 
                FROM position_songs 
                WHERE title = ? AND difficulty = ? 
                LIMIT 1
            ");
            $positionStmt->execute([$matchedSong, $difficulty]);
            $positionData = $positionStmt->fetch();
            
            // 楽曲がposition_songsにない場合はスキップ
            if (!$positionData) continue;
            
            // 既存スコアの確認（楽曲名、プレイヤーID、難易度で判定）
            $existingStmt = $db->prepare("
                SELECT id, score FROM scores 
                WHERE song_title = ? AND player_id = ? AND difficulty = ? AND is_average_mode = 0
                LIMIT 1
            ");
            $existingStmt->execute([$matchedSong, $currentUserPlayerId, $difficulty]);
            $existing = $existingStmt->fetch();

            if ($existing) {
                // 既存スコアより高い場合のみ更新
                if ($score > $existing['score']) {
                    $updateStmt = $db->prepare("
                        UPDATE scores SET 
                        score = ?, miss_count = ?, clear_lamp = ?, updated_by = ?, updated_by_user_id = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$score, $missCount, $clearLamp, $playerName, $_SESSION['user_id'], $existing['id']]);
                    $updated++;
                }
            } else {
                // 新規登録
                $insertStmt = $db->prepare("
                    INSERT INTO scores 
                    (song_title, player_id, difficulty, score, clear_lamp, miss_count, 
                     position_category, is_shinrisen, is_average_mode, updated_by, updated_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $matchedSong, $currentUserPlayerId, $difficulty, $score, $clearLamp, $missCount,
                    $positionData['position_category'], $positionData['is_shinrisen'], false, 
                    $playerName, $_SESSION['user_id']
                ]);
                $imported++;
            }
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "CSVインポートが完了しました",
        "processed" => $processed,
        "imported" => $imported,
        "updated" => $updated,  
        "skipped" => $skipped,
        "details" => [
            "total_songs_in_csv" => $processed,
            "successfully_matched" => ($imported + $updated),
            "player_name" => $playerName,
            "player_id" => $currentUserPlayerId
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "インポートエラー: " . $e->getMessage()
    ]);
}
?>