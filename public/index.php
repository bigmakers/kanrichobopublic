<?php
require_once __DIR__ . '/../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
$account = load_user_meta($user['email'], 'account');
$todo = load_user_meta($user['email'], 'todo');
$schedules = load_user_meta($user['email'], 'schedule');
$rx = load_user_meta($user['email'], 'rx');
$aff = read_json('affiliates.json', ['header' => '']);
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>薬局管理帳簿ウェブシステム</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="mono">
<?= $aff['header'] ?? '' ?>
<h1>薬局管理帳簿ウェブシステム</h1>
<?= member_nav(); ?>
<div class="card">
    <h2>アカウント情報</h2>
    <p>氏名: <?= htmlspecialchars($user['name'] ?? '') ?></p>
    <p>薬局: <?= htmlspecialchars($user['pharmacy'] ?? '') ?></p>
    <p>許可期限: <?= htmlspecialchars($account['permit_expiry'] ?? '') ?></p>
    <p>施設基準: <?= htmlspecialchars(implode(' / ', $account['facilities'] ?? [])) ?></p>
    <p>公費枠: <?= htmlspecialchars(implode(' / ', $account['public_frames'] ?? [])) ?></p>
    <p>保険薬局/機関コード: <?= htmlspecialchars($account['insurance_code'] ?? '') ?></p>
    <p>PMDAメディナビ: <?= htmlspecialchars($account['pmda'] ?? '') ?></p>
</div>
<div class="card">
    <h2>ショートカット</h2>
    <ul>
        <li><a href="/member/checklist.php">日次チェックリスト</a></li>
        <li><a href="/member/rx_counts.php">月次処方箋枚数</a></li>
        <li><a href="/member/my_schedule.php">マイスケジュール編集</a></li>
        <li><a href="/member/training_materials.php">研修教材</a></li>
        <li><a href="/member/print_month.php">印刷ビュー</a></li>
    </ul>
</div>
<div class="card">
    <h2>TODO</h2>
    <ul>
        <?php for ($i=0; $i<10; $i++): ?>
            <li><?= htmlspecialchars($todo[$i]['text'] ?? '') ?> <?= !empty($todo[$i]['done']) ? '✅' : '' ?></li>
        <?php endfor; ?>
    </ul>
</div>
<div class="card">
    <h2>マイスケジュール予定</h2>
    <ul>
        <?php foreach (($schedules['events'] ?? []) as $event): ?>
            <li><?= htmlspecialchars($event['date'] ?? '') ?> - <?= htmlspecialchars($event['title'] ?? '') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<div class="card">
    <h2>今月の処方箋枚数</h2>
    <p><?= htmlspecialchars($rx[date('Y-m')]['total'] ?? '未入力') ?></p>
</div>
</body>
</html>
