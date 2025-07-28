<?php
// public/api/search_by_player.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$player_name = isset($_GET['player']) ? $_GET['player'] : '';
$position_filter = isset($_GET['position']) ? $_GET['position'] : '';
$shinrisen_filter = isset($_GET['shinrisen']) ? intval($_GET['shinrisen']) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    if (empty($player_name)) {
        echo json_encode([
            'songs' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => $limit,
                'has_next' => false,
                'has_prev' => false
            ]
        ]);
        exit;
    }
    
    $where_conditions = [];
    $params = [$player_name];
    
    // ポジションフィルター
    if (!empty($position_filter)) {
        $where_conditions[] = "ps.position_category = ?";
        $params[] = $position_filter;
    }
    
    // 心理戦フィルター
    if ($shinrisen_filter !== null) {
        $where_conditions[] = "ps.is_shinrisen = ?";
        $params[] = $shinrisen_filter;
    }
    
    $additional_where = '';
    if (!empty($where_conditions)) {
        $additional_where = 'AND ' . implode(' AND ', $where_conditions);
    }
    
    // 総件数を取得
    $count_query = "
        SELECT COUNT(DISTINCT ps.title, ps.difficulty) as total 
        FROM scores s
        JOIN players p ON s.player_id = p.id
        JOIN position_songs ps ON s.song_title = ps.title AND s.difficulty = ps.difficulty
        WHERE p.name LIKE CONCAT('%', ?, '%')
        {$additional_where}
    ";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);
    
    // メインクエリ - プレイヤーがスコア登録している楽曲を取得
    $query = "
        SELECT DISTINCT ps.title, ps.difficulty, ps.position_category, ps.is_shinrisen, ps.total_notes,
               s.score, s.clear_lamp, s.miss_count, s.recommended_options, s.notes,
               p.name as player_name, p.is_opponent
        FROM scores s
        JOIN players p ON s.player_id = p.id
        JOIN position_songs ps ON s.song_title = ps.title AND s.difficulty = ps.difficulty
        WHERE p.name LIKE CONCAT('%', ?, '%')
        {$additional_where}
        ORDER BY ps.position_category, ps.is_shinrisen, ps.title, ps.difficulty
        LIMIT {$limit} OFFSET {$offset}
    ";
              
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $songs_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $songs_arr[] = $row;
    }

    // レスポンス
    $response = [
        'songs' => $songs_arr,
        'search_type' => 'player',
        'player_name' => $player_name,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(array("error" => "検索エラー: " . $e->getMessage()));
}
?>