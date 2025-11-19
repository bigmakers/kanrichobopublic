<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
$history = load_user_meta($user['email'], 'training_history');
$yearAgo = strtotime('-1 year');
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>研修受講印刷</title><link rel="stylesheet" href="../styles.css"></head>
<body class="print">
<h2>直近1年の研修履歴</h2>
<ul>
<?php foreach ($history as $h): $d=strtotime($h['date'] ?? ''); if ($d && $d >= $yearAgo): ?>
    <li><?= htmlspecialchars($h['date']) ?> - <?= htmlspecialchars($h['id']) ?></li>
<?php endif; endforeach; ?>
</ul>
</body></html>
