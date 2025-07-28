-- IIDX スコアマネージャー用サンプルデータ
-- このSQLをphpMyAdminで実行してテスト用楽曲データを追加してください

USE skyponet_iidxscoremanager;

-- サンプル楽曲データを position_songs テーブルに挿入
INSERT INTO position_songs (position_category, song_number, title, difficulty, is_shinrisen, total_notes) VALUES
-- 先鋒
('先鋒', 1, '5.1.1.', 'ANOTHER', 0, 1999),
('先鋒', 2, 'AAA', 'ANOTHER', 0, 2250),
('先鋒', 3, 'Aegis', 'ANOTHER', 0, 1844),
('先鋒', 4, 'ANTHEM LANDING', 'ANOTHER', 0, 1600),
('先鋒', 5, 'Apocalypse ～dirge of swans～', 'ANOTHER', 0, 2036),

-- 次鋒  
('次鋒', 1, 'Arousing', 'ANOTHER', 0, 1955),
('次鋒', 2, 'Back Spin Sweeper', 'ANOTHER', 0, 1721),
('次鋒', 3, 'Beat Juggling Mix', 'ANOTHER', 0, 1811),
('次鋒', 4, 'Broadbanded Network!!', 'ANOTHER', 0, 1924),
('次鋒', 5, 'Critical Crystal', 'ANOTHER', 0, 1888),

-- 中堅
('中堅', 1, 'Devil Fish Dumpling', 'ANOTHER', 0, 1802),
('中堅', 2, 'DJ BATTLE', 'ANOTHER', 0, 1674),
('中堅', 3, 'Draconic Style', 'ANOTHER', 0, 1933),
('中堅', 4, 'Drum \'n\' Bass', 'ANOTHER', 0, 1555),
('中堅', 5, 'EXE', 'ANOTHER', 0, 1701),

-- 副将
('副将', 1, 'Freedom', 'ANOTHER', 0, 1644),
('副将', 2, 'Ganymede', 'ANOTHER', 0, 1890),
('副将', 3, 'Innocent Walls', 'ANOTHER', 0, 2066),
('副将', 4, 'Last Message', 'ANOTHER', 0, 1978),
('副将', 5, 'Liberation', 'ANOTHER', 0, 1801),

-- 大将
('大将', 1, 'Make Me Your Own', 'ANOTHER', 0, 1722),
('大将', 2, 'No.13', 'ANOTHER', 0, 1755),
('大将', 3, 'Pluto', 'ANOTHER', 0, 1896),
('大将', 4, 'Scripted Connection⇒', 'ANOTHER', 0, 1971),
('大将', 5, 'Tomorrow Perfume', 'ANOTHER', 0, 1644),

-- 心理戦楽曲
('先鋒', 6, '255', 'ANOTHER', 1, 2555),
('次鋒', 6, 'MENDES', 'ANOTHER', 1, 2444),
('中堅', 6, 'SOLID STATE SQUAD', 'ANOTHER', 1, 2333),
('副将', 6, 'Verflucht', 'ANOTHER', 1, 2222),
('大将', 6, 'one or eight', 'ANOTHER', 1, 2111);

-- テスト用ユーザー（パスワードは 'password' をハッシュ化したもの）
INSERT INTO users (username, password, team) VALUES
('test_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KBM'),
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KBM');

-- テスト用プレイヤー
INSERT INTO players (id, name, team, is_opponent) VALUES
(1001, 'test_user', 'KBM', 0),
(1002, 'admin', 'KBM', 0),
(1, 'ゆうすけ', 'BBD', 1),
(2, 'イリス', 'BBD', 1),
(3, 'ゆーと', 'BBD', 1),
(4, 'まぐ', 'BBD', 1),
(5, 'レクリア', 'BBD', 1),
(6, 'みゆ', 'BBD', 1);

-- サンプルスコア
INSERT INTO scores (song_title, player_id, difficulty, score, clear_lamp, miss_count, position_category, is_shinrisen, is_average_mode, updated_by, updated_by_user_id) VALUES
('5.1.1.', 1001, 'ANOTHER', 1800, 'NC', 5, '先鋒', 0, 0, 'test_user', 1),
('AAA', 1001, 'ANOTHER', 2100, 'HC', 2, '先鋒', 0, 0, 'test_user', 1),
('5.1.1.', 1, 'ANOTHER', 1750, 'NC', 8, '先鋒', 0, 0, 'ゆうすけ', NULL),
('AAA', 2, 'ANOTHER', 2050, 'NC', 4, '先鋒', 0, 0, 'イリス', NULL);