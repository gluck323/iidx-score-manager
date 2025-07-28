<?php
// public/api/search.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();

session_start();

$search_term = isset($_GET['q']) ? $_GET['q'] : '';
$position_filter = isset($_GET['position']) ? $_GET['position'] : '';
$shinrisen_filter = isset($_GET['shinrisen']) ? intval($_GET['shinrisen']) : null;
$favorites_only = isset($_GET['favorites']) ? intval($_GET['favorites']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $where_conditions = [];
    $params = [];
    
    // 楽曲名検索
    if (!empty($search_term)) {
        $where_conditions[] = "ps.title LIKE ?";
        $params[] = "%{$search_term}%";
    }
    
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
    
    // お気に入りフィルター
    $join_clause = '';
    if ($favorites_only && isset($_SESSION['user_id'])) {
        $join_clause = "JOIN favorites f ON ps.title = f.song_title AND ps.difficulty = f.difficulty";
        $where_conditions[] = "f.user_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // 総件数を取得
    $count_query = "SELECT COUNT(DISTINCT ps.title) as total FROM position_songs ps {$join_clause} {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);
    
    // メインクエリ
    $query = "SELECT DISTINCT ps.title, ps.difficulty, ps.position_category, ps.is_shinrisen, ps.total_notes 
              FROM position_songs ps {$join_clause}
              {$where_clause}
              ORDER BY ps.position_category, ps.is_shinrisen, ps.song_number ASC
              LIMIT {$limit} OFFSET {$offset}";
              
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $songs_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $songs_arr[] = $row;
    }

    // レスポンス
    $response = [
        'songs' => $songs_arr,
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