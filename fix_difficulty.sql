-- scoresテーブルのdifficultyが空文字になっている問題を修正
-- phpMyAdminで実行してください

USE skyponet_iidxscoremanager;

-- 現在のscoresテーブルの状況を確認
SELECT song_title, difficulty, COUNT(*) as count 
FROM scores 
GROUP BY song_title, difficulty 
ORDER BY song_title;

-- 空文字のdifficultyを'ANOTHER'に更新
-- （ほとんどの楽曲がANOTHER譜面のため）
UPDATE scores 
SET difficulty = 'ANOTHER' 
WHERE difficulty = '' OR difficulty IS NULL;

-- 更新後の確認
SELECT song_title, difficulty, COUNT(*) as count 
FROM scores 
GROUP BY song_title, difficulty 
ORDER BY song_title;

-- 特定の楽曲で異なる難易度を設定したい場合の例：
-- UPDATE scores SET difficulty = 'HYPER' WHERE song_title = '特定楽曲名' AND difficulty = 'ANOTHER';