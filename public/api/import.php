<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once '../../src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, team, is_opponent FROM players ORDER BY is_opponent DESC, name ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$players_arr = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $players_arr[] = $row;
}

echo json_encode($players_arr);