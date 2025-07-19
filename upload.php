<?php
// Composerのオートローダーを読み込む
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

// レスポンスの形式をJSONに指定
header('Content-Type: application/json');

// POSTデータとファイルの受け取り
$student_id = $_POST['student_id'] ?? '';
$genre = $_POST['genre'] ?? ''; // ジャンルを受け取る
$image_file = $_FILES['image_file'] ?? null;

// ▼▼▼ ここを修正 ▼▼▼
// ジャンルが選択されていない（空文字列の）場合、「未分類」をセット
if (empty($genre)) {
    $genre = '未分類';
}
// ▲▲▲ ここまで ▲▲▲

if (empty($student_id) || $image_file === null || $image_file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '必須項目が正しく送信されていません。']);
    exit;
}

try {
    // Firebase Admin SDKの初期化
    $factory = (new Factory)
        ->withServiceAccount(__DIR__ . '/../private/key.json') 
        ->withDefaultStorageBucket('monokashi.firebasestorage.app');

    $storage = $factory->createStorage();
    $bucket = $storage->getBucket();

    // ファイル名を生成
    $image_name = 'images/' . $student_id . '_' . time() . '_' . basename($image_file['name']);
    
    $fileContents = file_get_contents($image_file['tmp_name']);

    // アップロードされたファイルのMIMEタイプを判別
    $mimeType = mime_content_type($image_file['tmp_name']) ?: 'application/octet-stream';

    // Firebase Storageにファイルをアップロード (MIMEタイプを指定)
    $bucket->upload($fileContents, [
        'name' => $image_name,
        'metadata' => [
            'contentType' => $mimeType
        ]
    ]);

    // データベース接続情報
    $dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
    $db_user = 'tamago';
    $db_password = 'fV8b5qEQ';

    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // データベースに情報を登録
    $sql = 'INSERT INTO photos (student_id, genre, image_name) VALUES (?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $genre, $image_name]);

    echo json_encode(['success' => true, 'message' => 'アップロードが完了しました。']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'サーバーエラー: ' . $e->getMessage()]);
    exit;
}
?>