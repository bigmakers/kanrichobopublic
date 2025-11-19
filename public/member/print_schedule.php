<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
$schedules = load_user_meta($user['email'], 'schedule');
$yearAgo = strtotime('-1 year');
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>スケジュール参加実績</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="print">
<h2>直近1年の参加済みイベント</h2>
<ul>
<?php foreach (($schedules['events'] ?? []) as $event): $d=strtotime($event['date'] ?? ''); if ($d && $d >= $yearAgo): ?>
    <li><?= htmlspecialchars($event['date']) ?> - <?= htmlspecialchars($event['title']) ?></li>
<?php endif; endforeach; ?>
</ul>
</body></html>
