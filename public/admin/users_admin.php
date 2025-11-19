<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
$users = users_all();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($_POST['email'] ?? '');
    $name = sanitize_text($_POST['name'] ?? '');
    $pharmacy = sanitize_text($_POST['pharmacy'] ?? '');
    $password = $_POST['password'] ?? '';
    $meta = [
        'permit_expiry' => sanitize_text($_POST['permit_expiry'] ?? ''),
        'facilities' => explode(',', sanitize_text($_POST['facilities'] ?? '')),
        'public_frames' => explode(',', sanitize_text($_POST['public_frames'] ?? '')),
        'insurance_code' => sanitize_text($_POST['insurance_code'] ?? ''),
        'pmda' => sanitize_text($_POST['pmda'] ?? '')
    ];
    $user = auth_user_by_email($email) ?? ['email'=>$email,'password'=>'','is_admin'=>false];
    $user['name'] = $name;
    $user['pharmacy'] = $pharmacy;
    $user['meta'] = $meta;
    if ($password) $user['password'] = password_hash($password, PASSWORD_DEFAULT);
    save_user($user);
    $message = '保存しました';
    $users = users_all();
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>ユーザー管理</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>ユーザー管理</h1>
<?= admin_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>メール<input type="email" name="email" required></label>
    <label>氏名<input type="text" name="name"></label>
    <label>薬局<input type="text" name="pharmacy"></label>
    <label>パスワード<input type="password" name="password" placeholder="変更時のみ"></label>
    <label>許可期限<input type="date" name="permit_expiry"></label>
    <label>施設基準<input type="text" name="facilities"></label>
    <label>公費枠<input type="text" name="public_frames"></label>
    <label>保険コード<input type="text" name="insurance_code"></label>
    <label>PMDA情報<input type="text" name="pmda"></label>
    <button type="submit">保存</button>
</form>
<div class="card">
    <h3>ユーザー一覧</h3>
    <ul>
        <?php foreach ($users as $u): ?>
            <li><?= htmlspecialchars($u['email']) ?> - <?= htmlspecialchars($u['name'] ?? '') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
</body></html>
