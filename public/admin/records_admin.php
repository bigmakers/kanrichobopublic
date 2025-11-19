<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
$users = users_all();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($_POST['email'] ?? '');
    $date = sanitize_text($_POST['date'] ?? '');
    if ($email && $date) {
        $checklists = load_user_meta($email, 'checklists');
        unset($checklists[$date]);
        save_user_meta($email, 'checklists', $checklists);
        $message = '削除しました';
    }
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>記録管理</title><link rel="stylesheet" href="../styles.css"></head>
<body class="mono">
<h1>記録管理</h1>
<?= admin_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>ユーザー<select name="email">
        <?php foreach ($users as $u): ?><option value="<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></option><?php endforeach; ?>
    </select></label>
    <label>日付<input type="date" name="date"></label>
    <button type="submit">該当チェックリストを削除</button>
</form>
</body></html>
