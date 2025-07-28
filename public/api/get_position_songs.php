<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once '../../src/config/database.php';

$position = $_GET['position'] ?? '';
$is_shinrisen = isset($_GET['shinrisen']) ? (bool)$_GET['shinrisen'] : false;

$database = new Database();
$db = $database->getConnection();

try {
    if ($position) {
        $query = "SELECT * FROM position_songs WHERE position_category = ? AND is_shinrisen = ? ORDER BY song_number ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$position, $is_shinrisen ? 1 : 0]);
    } else {
        $query = "SELECT * FROM position_songs ORDER BY position_category, is_shinrisen, song_number ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
    }
    
    $songs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $songs[] = $row;
    }
    
    echo json_encode($songs);
    
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
