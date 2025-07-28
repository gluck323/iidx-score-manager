<?php
// scripts/import_excel_data.php
require_once '../src/config/database.php';
require_once '../src/models/Song.php';

// アップロードされたExcelファイルから抽出したデータ
$songs_data = [
    ["No." => 1, "曲名" => "Dr.LOVE", "アーティスト" => "baby weapon feat.Asuka.M", "SPB" => "-", "SPN" => "2", "SPH" => "3", "SPA" => 4, "SPL" => "-", "備考" => ""],
    ["No." => 2, "曲名" => "GAMBOL", "アーティスト" => "SLAKE", "SPB" => "-", "SPN" => "2", "SPH" => "2", "SPA" => "-", "SPL" => "-", "備考" => ""],
    ["No." => 3, "曲名" => "GRADIUSIC CYBER", "アーティスト" => "TAKA", "SPB" => "-", "SPN" => "5", "SPH" => "6", "SPA" => 7, "SPL" => "-", "備考" => ""]
    // ... 1793曲のデータを全て追加
];

$database = new Database();
$db = $database->getConnection();
$song = new Song($db);

try {
    echo "データインポート開始...\n";
    $imported_count = $song->importFromExcel($songs_data);
    echo "完了: {$imported_count}曲をインポートしました\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

// 実際のExcelデータを使用する場合のPHPExcelライブラリ使用例
/*
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('対策用曲リスト.xlsx');
$worksheet = $spreadsheet->getSheet(3); // 曲リストシート

$songs_data = [];
$highestRow = $worksheet->getHighestRow();

for ($row = 2; $row <= $highestRow; $row++) {
    $songs_data[] = [
        'No.' => $worksheet->getCell('A' . $row)->getValue(),
        '曲名' => $worksheet->getCell('B' . $row)->getValue(),
        'アーティスト' => $worksheet->getCell('C' . $row)->getValue(),
        'SPB' => $worksheet->getCell('D' . $row)->getValue(),
        'SPN' => $worksheet->getCell('E' . $row)->getValue(),
        'SPH' => $worksheet->getCell('F' . $row)->getValue(),
        'SPA' => $worksheet->getCell('G' . $row)->getValue(),
        'SPL' => $worksheet->getCell('H' . $row)->getValue(),
        '備考' => $worksheet->getCell('I' . $row)->getValue()
    ];
}
*/
?>