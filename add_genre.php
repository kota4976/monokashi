<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '認証が必要です。']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$new_genre = $input['genre_name'] ?? null;

if (empty($new_genre)) {
    echo json_encode(['success' => false, 'message' => 'ジャンル名が入力されていません。']);
    exit;
}

// データベース接続情報
$dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
$db_user = 'tamago';
$db_password = 'fV8b5qEQ';

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = 'INSERT INTO genres (name) VALUES (?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_genre]);

    echo json_encode(['success' => true, 'message' => '新しいジャンルを追加しました。']);

} catch (\Throwable $e) {
    // UNIQUE制約違反(重複)のエラーコードは 23000
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'そのジャン名は既に使用されています。']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
    }
    exit;
}
?>