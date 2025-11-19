<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!empty($_SESSION['user'])) {
    header('Location: ' . url_for('index.php'));
    exit;
}
$message = '';
$info = sanitize_text($_GET['msg'] ?? '');
$email = '';
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = auth_user_by_email($email);
    if ($user && password_verify($password, $user['password'])) {
        login($user);
        header('Location: ' . url_for('index.php'));
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
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<h1>薬局管理帳簿ウェブシステム ログイン</h1>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($info && !$message): ?><div class="alert">
    <?= htmlspecialchars($info) ?>
</div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>メール
        <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES) ?>">
    </label>
    <label>パスワード
        <input type="password" name="password" required autocomplete="current-password" aria-describedby="login-help">
    </label>
    <p id="login-help" class="muted">登録したメールアドレスとパスワードでログインしてください。</p>
    <button type="submit">ログイン</button>
</form>
<p><a href="<?= htmlspecialchars(url_for('register.php'), ENT_QUOTES) ?>">新規登録はこちら</a></p>
</body>
</html>
