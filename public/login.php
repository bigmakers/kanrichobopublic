<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}
$message = '';
$info = sanitize_text($_GET['msg'] ?? '');
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = auth_user_by_email($email);
    if ($user && password_verify($password, $user['password'])) {
        login($user);
        header('Location: /index.php');
        exit;
    }
    $message = '認証に失敗しました';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - 薬局管理帳簿ウェブシステム</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body class="mono">
<h1>薬局管理帳簿ウェブシステム ログイン</h1>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($info && !$message): ?><div class="alert">
    <?= htmlspecialchars($info) ?>
</div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>メール<input type="email" name="email" required></label>
    <label>パスワード<input type="password" name="password" required></label>
    <button type="submit">ログイン</button>
</form>
<p><a href="/register.php">新規登録はこちら</a></p>
</body>
</html>
