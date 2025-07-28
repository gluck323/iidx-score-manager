// public/api/stats.php - 統計情報取得
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stats = [];
    
    // ポジション別統計
    $positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
    foreach ($positions as $position) {
        $stmt = $db->prepare("SELECT COUNT(*) as normal_count FROM position_songs WHERE position_category = ? AND is_shinrisen = 0");
        $stmt->execute([$position]);
        $normal = $stmt->fetch()['normal_count'];
        
        $stmt = $db->prepare("SELECT COUNT(*) as shinrisen_count FROM position_songs WHERE position_category = ? AND is_shinrisen = 1");
        $stmt->execute([$position]);
        $shinrisen = $stmt->fetch()['shinrisen_count'];
        
        $stats[$position] = [
            'normal' => $normal,
            'shinrisen' => $shinrisen,
            'total' => $normal + $shinrisen
        ];
    }
    
    // 全体統計
    $stmt = $db->query("SELECT COUNT(*) as total FROM position_songs");
    $stats['total'] = $stmt->fetch()['total'];
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>