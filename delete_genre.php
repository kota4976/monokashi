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
$genre_name = $input['genre_name'] ?? null;

if (empty($genre_name)) {
    echo json_encode(['success' => false, 'message' => 'ジャンル名が指定されていません。']);
    exit;
}

// 基本のジャンルは削除させない
$protected_genres = ['小物(専門)', '小物(一般)', '未分類', 'その他'];
if (in_array($genre_name, $protected_genres, true)) {
    echo json_encode(['success' => false, 'message' => '基本のジャンルは削除できません。']);
    exit;
}

// データベース接続情報
$dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
$db_user = 'tamago';
$db_password = 'fV8b5qEQ';

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 削除する前に、そのジャンルが使われているか確認（任意）
    $check_sql = 'SELECT COUNT(*) FROM photos WHERE genre = ?';
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$genre_name]);
    if ($check_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'このジャンルは既に使用されているため削除できません。']);
        exit;
    }

    // ジャンルを削除
    $sql = 'DELETE FROM genres WHERE name = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$genre_name]);

    echo json_encode(['success' => true, 'message' => 'ジャンルを削除しました。']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
    exit;
}
?>