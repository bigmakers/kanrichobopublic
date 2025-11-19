<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
$users = users_all();
$status = security_status();
$alerts = scan_data_security();
$aff = read_json('affiliates.json', ['header' => '']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html = preg_replace('/[\x00-\x1F\x7F]/u', '', $_POST['header_html'] ?? '');
    json_write_atomic('affiliates.json', ['header' => $html]);
    $aff['header'] = $html;
    $message = 'ヘッダーHTMLを更新しました';
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>管理ダッシュボード</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<h1>管理ダッシュボード</h1>
<?= admin_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="card">
    <h3>KPI</h3>
    <p>ユーザー数: <?= count($users) ?></p>
</div>
<div class="card">
    <h3>セキュリティステータス</h3>
    <ul>
        <li>HTTPOnly: <?= htmlspecialchars($status['cookie_httponly']) ?></li>
        <li>Strict-Mode: <?= htmlspecialchars($status['use_strict_mode']) ?></li>
        <li>Secure: <?= htmlspecialchars($status['cookie_secure']) ?></li>
        <li>SameSite: <?= htmlspecialchars($status['cookie_samesite']) ?></li>
    </ul>
</div>
<div class="card">
    <h3>監査</h3>
    <ul>
        <?php foreach ($alerts as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?>
        <?php if (empty($alerts)): ?><li>問題なし</li><?php endif; ?>
    </ul>
</div>
<div class="card">
    <h3>ヘッダーアフィリエイトHTML</h3>
    <form method="post">
        <?= csrf_field(); ?>
        <textarea name="header_html" rows="4"><?= htmlspecialchars($aff['header'] ?? '') ?></textarea>
        <button type="submit">更新</button>
    </form>
</div>
</body></html>
