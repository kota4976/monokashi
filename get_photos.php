<?php
// Composerのオートローダーを読み込む
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;


// レスポンスの形式をJSONに指定
header('Content-Type: application/json');

try {
    // Firebase Admin SDKの初期化
    $factory = (new Factory)
        ->withServiceAccount(__DIR__ . '/../private/key.json')
        // ★★★ 修正: appspot.com に変更
        ->withDefaultStorageBucket('monokashi.firebasestorage.app');

    $storage = $factory->createStorage();
    
    // データベース接続情報
    $dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8';
    $db_user = 'tamago';      // ★★★ MySQLのユーザー名
    $db_password = 'fV8b5qEQ';  // ★★★ MySQLのパスワード

    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // DBから写真情報を取得
    $stmt = $pdo->query('SELECT student_id, genre, image_name, created_at, return_date FROM photos ORDER BY created_at DESC');
    $photos_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $photos_with_urls = [];
    foreach ($photos_from_db as $photo) {
        // Firebase Storageから署名付きURL（有効期限付きの公開URL）を生成
        $image_reference = $storage->getBucket()->object($photo['image_name']);
        
        // URLの有効期限を1時間に設定
        $expiresAt = new \DateTime('+1 hour');
        $imageUrl = $image_reference->signedUrl($expiresAt);
        
        // 元のデータに画像URLを追加
        $photo['imageUrl'] = $imageUrl;
        $photos_with_urls[] = $photo;
    }

    // 最終的なデータをJSONで出力
    echo json_encode($photos_with_urls);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'サーバーエラー: ' . $e->getMessage()]);
    exit;
}
?>