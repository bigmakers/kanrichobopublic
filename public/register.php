<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}
$message = '';
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
        header('Location: /index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー登録</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="mono">
<h1>ユーザー登録</h1>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>氏名<input type="text" name="name" required></label>
    <label>メール<input type="email" name="email" required></label>
    <label>所属薬局<input type="text" name="pharmacy"></label>
    <label>パスワード<input type="password" name="password" required></label>
    <button type="submit">登録</button>
</form>
<p><a href="/login.php">ログインに戻る</a></p>
</body>
</html>
