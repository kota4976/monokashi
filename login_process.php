<?php
// セッションを開始
session_start();

// データベース接続情報
$dsn = 'mysql:dbname=mono_db;host=localhost;charset=utf8'; // データベース名を指定
$user = 'tamago';      // 作成したユーザー名を指定
$password = 'fV8b5qEQ';  // 作成したユーザーのパスワードを指定

// フォームからの入力を受け取る
$username = $_POST['username'];
$input_password = $_POST['password'];

// データベースへの接続
try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 入力されたユーザー名でユーザーを検索
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在し、かつパスワードが一致するか検証
    if ($user && password_verify($input_password, $user['password'])) {
        // 認証成功
        // セッションにユーザー情報を保存
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // ログイン後のページにリダイレクト
        header('Location: dashboard.php');
        exit;
    } else {
        // 認証失敗
        echo 'ユーザー名またはパスワードが間違っています。';
        // 必要に応じてログインページにリダイレクト
        // header('Location: login.php');
        // exit;
    }

} catch (PDOException $e) {
    // データベース接続エラー
    echo 'データベースエラー: ' . $e->getMessage();
    exit;
}
?>