<?php
header('Content-Type: application/json');

// POSTされてきたJSONデータをデコード
$input = json_decode(file_get_contents('php://input'), true);

$image_name = $input['image_name'] ?? null;
$genre = $input['genre'] ?? null; // ★★★ 単一のジャンルとして受け取る
$return_date = $input['return_date'] ?? null;

// image_nameがなければ処理を中断
if (!$image_name) {
    echo json_encode(['success' => false, 'message' => '対象の画像が指定されていません。']);
    exit;
}

// 返却日が空文字列の場合はNULLに変換
if ($return_date === '') {
    $return_date = null;
}

try {
    // データベース接続情報
    $dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
    $db_user = 'tamago';
    $db_password = 'fV8b5qEQ';

    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ★★★ 単一のジャンルをそのまま保存するSQLに変更 ★★★
    $sql = 'UPDATE photos SET genre = ?, return_date = ? WHERE image_name = ?';
    $stmt = $pdo->prepare($sql);
    
    // 実行
    $stmt->execute([$genre, $return_date, $image_name]);

    echo json_encode(['success' => true, 'message' => '情報を更新しました。']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
    exit;
}
?>