<?php
header('Content-Type: application/json');

// データベース接続情報
$dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
$db_user = 'tamago';
$db_password = 'fV8b5qEQ';

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- ▼▼▼ SQLクエリを修正 ▼▼▼ ---
    $sql = "
        SELECT name FROM genres
        ORDER BY
          CASE
            WHEN name = '未分類' THEN 1
            WHEN name = '小物（専門）' THEN 2
            WHEN name = '小物（一般）' THEN 3
            WHEN name = 'その他' THEN 9999
            ELSE 4
          END,
          name ASC
    ";
    // --- ▲▲▲ ここまで ▲▲▲ ---

    $stmt = $pdo->query($sql);
    $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($genres);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}
?>