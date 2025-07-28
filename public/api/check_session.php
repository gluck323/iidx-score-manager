<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "logged_in" => true,
        "user" => [
            "id" => $_SESSION['user_id'],
            "username" => $_SESSION['username'],
            "team" => $_SESSION['team']
        ]
    ]);
} else {
    echo json_encode(["logged_in" => false]);
}
?>