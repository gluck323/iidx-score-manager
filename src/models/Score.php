<?php
class Score {
    private $conn;
    private $table_name = "scores";

    public function __construct($db) {
        $this->conn = $db;
    }

    function getByPlayerAndSong($player_id, $song_id, $position_category) {
        $query = "SELECT sc.*, s.title, s.artist, p.name as player_name
                  FROM " . $this->table_name . " sc
                  JOIN songs s ON sc.song_id = s.id
                  JOIN players p ON sc.player_id = p.id
                  WHERE sc.player_id = ? AND sc.song_id = ? AND sc.position_category = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$player_id, $song_id, $position_category]);
        
        return $stmt;
    }

    function upsert($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (song_id, player_id, difficulty, score, clear_lamp, miss_count, 
                  recommended_options, notes, position_category, is_shinrisen, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 score = VALUES(score),
                 clear_lamp = VALUES(clear_lamp),
                 miss_count = VALUES(miss_count),
                 recommended_options = VALUES(recommended_options),
                 notes = VALUES(notes),
                 updated_by = VALUES(updated_by),
                 updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['song_id'],
            $data['player_id'],
            $data['difficulty'],
            $data['score'],
            $data['clear_lamp'],
            $data['miss_count'],
            $data['recommended_options'],
            $data['notes'],
            $data['position_category'],
            $data['is_shinrisen'],
            $data['updated_by']
        ]);
    }
}
?>