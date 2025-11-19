<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!empty($_SESSION['user'])) {
    header('Location: ' . url_for('index.php'));
    exit;
}
$message = '';
$name = '';
$email = '';
$pharmacy = '';
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($_POST['email'] ?? '');
    $name = sanitize_text($_POST['name'] ?? '');
    $pharmacy = sanitize_text($_POST['pharmacy'] ?? '');
    $password = $_POST['password'] ?? '';
    if (auth_user_by_email($email)) {
        $message = '既に登録済みです';
    } else {
        $user = [
            'email' => strtolower($email),
            'name' => $name,
            'pharmacy' => $pharmacy,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'meta' => [],
            'is_admin' => false
        ];
        save_user($user);
        login($user);
        header('Location: ' . url_for('index.php'));
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー登録</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<h1>ユーザー登録</h1>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>氏名
        <input type="text" name="name" required autocomplete="name" value="<?= htmlspecialchars($name ?? '', ENT_QUOTES) ?>">
    </label>
    <label>メール
        <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES) ?>">
    </label>
    <label>所属薬局
        <input type="text" name="pharmacy" value="<?= htmlspecialchars($pharmacy ?? '', ENT_QUOTES) ?>" placeholder="任意">
    </label>
    <label>パスワード
        <input type="password" name="password" required autocomplete="new-password" aria-describedby="pw-hint">
    </label>
    <p id="pw-hint" class="muted">8文字以上の安全なパスワードを設定してください。</p>
    <button type="submit">登録</button>
</form>
<p><a href="<?= htmlspecialchars(url_for('login.php'), ENT_QUOTES) ?>">ログインに戻る</a></p>
</body>
</html>
