<?php
// Composerのオートローダーを読み込む
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

// セッションを開始し、ログイン状態を確認
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '管理者としてログインしていません。']);
    exit;
}

header('Content-Type: application/json');

// POSTされてきたJSONデータをデコード
$input = json_decode(file_get_contents('php://input'), true);
$image_name = $input['image_name'] ?? null;

if (!$image_name) {
    echo json_encode(['success' => false, 'message' => '削除対象の画像が指定されていません。']);
    exit;
}

try {
    // 1. Firebase Storageからファイルを削除
    $factory = (new Factory)
        ->withServiceAccount(__DIR__ . '/../private/key.json') 
        ->withDefaultStorageBucket('monokashi.firebasestorage.app');

    $storage = $factory->createStorage();
    $image_reference = $storage->getBucket()->object($image_name);

    if ($image_reference->exists()) {
        $image_reference->delete();
    }

    // 2. MySQLデータベースからレコードを削除
    $dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
    $db_user = 'tamago';
    $db_password = 'fV8b5qEQ';

    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = 'DELETE FROM photos WHERE image_name = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$image_name]);

    echo json_encode(['success' => true, 'message' => '削除が完了しました。']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '削除処理中にエラーが発生しました: ' . $e->getMessage()]);
    exit;
}
?>