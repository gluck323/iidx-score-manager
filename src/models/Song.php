<?php
class Song {
    private $conn;
    private $table_name = "songs";

    public $id;
    public $song_number;
    public $title;
    public $artist;
    public $spb_level;
    public $spn_level;
    public $sph_level;
    public $spa_level;
    public $spl_level;
    public $remarks;

    public function __construct($db) {
        $this->conn = $db;
    }

    function search($search_term) {
        $query = "SELECT s.*, 
                  (SELECT COUNT(*) FROM scores sc WHERE sc.song_id = s.id) as score_count
                  FROM " . $this->table_name . " s 
                  WHERE s.title LIKE ? OR s.artist LIKE ?
                  ORDER BY s.title ASC";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%{$search_term}%";
        $stmt->bindParam(1, $search_term);
        $stmt->bindParam(2, $search_term);
        $stmt->execute();
        
        return $stmt;
    }

    function searchWithPlayer($player_name, $song_title) {
        $query = "SELECT s.*, sc.*, p.name as player_name
                  FROM " . $this->table_name . " s 
                  LEFT JOIN scores sc ON s.id = sc.song_id
                  LEFT JOIN players p ON sc.player_id = p.id
                  WHERE s.title LIKE ? AND p.name LIKE ?
                  ORDER BY s.title ASC";
        
        $stmt = $this->conn->prepare($query);
        $song_title = "%{$song_title}%";
        $player_name = "%{$player_name}%";
        $stmt->bindParam(1, $song_title);
        $stmt->bindParam(2, $player_name);
        $stmt->execute();
        
        return $stmt;
    }

    function importFromExcel($songs_data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (song_number, title, artist, spb_level, spn_level, sph_level, spa_level, spl_level, remarks) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($songs_data as $song) {
            $stmt->execute([
                $song['No.'] ?? 0,
                $song['曲名'] ?? '',
                $song['アーティスト'] ?? '',
                $song['SPB'] === '-' ? null : $song['SPB'],
                $song['SPN'] === '-' ? null : $song['SPN'],
                $song['SPH'] === '-' ? null : $song['SPH'],
                $song['SPA'] === '-' ? null : $song['SPA'],
                $song['SPL'] === '-' ? null : $song['SPL'],
                $song['備考'] ?? ''
            ]);
        }
        
        return $stmt->rowCount();
    }
}