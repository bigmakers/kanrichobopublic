<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$account = load_user_meta($user['email'], 'account');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete_account') {
        delete_user_records($user['email']);
        logout();
        header('Location: /login.php?msg=' . urlencode('退会処理が完了しました。ご利用ありがとうございました。'));
        exit;
    }
    $fields = ['permit_expiry','insurance_code','pmda'];
    foreach ($fields as $f) {
        $account[$f] = sanitize_text($_POST[$f] ?? '');
    }
    $account['facilities'] = array_filter(array_map('sanitize_text', explode(',', $_POST['facilities'] ?? '')));
    $account['public_frames'] = array_filter(array_map('sanitize_text', explode(',', $_POST['public_frames'] ?? '')));
    save_user_meta($user['email'], 'account', $account);
    $message = '保存しました';
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>アカウント設定</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>アカウント設定</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <label>許可期限<input type="date" name="permit_expiry" value="<?= htmlspecialchars($account['permit_expiry'] ?? '') ?>"></label>
    <label>施設基準（カンマ区切り）<input type="text" name="facilities" value="<?= htmlspecialchars(implode(',', $account['facilities'] ?? [])) ?>"></label>
    <label>公費枠（カンマ区切り）<input type="text" name="public_frames" value="<?= htmlspecialchars(implode(',', $account['public_frames'] ?? [])) ?>"></label>
    <label>保険薬局/機関コード<input type="text" name="insurance_code" value="<?= htmlspecialchars($account['insurance_code'] ?? '') ?>"></label>
    <label>PMDAメディナビ情報<input type="text" name="pmda" value="<?= htmlspecialchars($account['pmda'] ?? '') ?>"></label>
    <button type="submit">保存</button>
</form>

<div class="card danger">
    <h3>退会</h3>
    <p>退会すると個人の記録は削除されます。必要に応じて事前に<a href="/member/backup.php">バックアップ</a>を取得してください。</p>
    <form method="post" onsubmit="return confirm('バックアップは取得しましたか？退会するとデータが削除されます。よろしいですか？');">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="delete_account">
        <button type="submit">退会する</button>
    </form>
</div>
</body></html>
