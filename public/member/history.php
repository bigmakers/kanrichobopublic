<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$checklists = load_user_meta($user['email'], 'checklists');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row = $_POST['entry'] ?? [];
    $date = sanitize_text($row['date'] ?? '');
    if ($date !== '') {
        $checksRaw = sanitize_text($row['checks'] ?? '');
        $checks = array_filter(array_map('trim', preg_split('/[,\n]+/u', $checksRaw)));
        $prev = $checklists[$date] ?? [];
        $checklists[$date] = [
            'items' => ['text' => sanitize_text($row['items'] ?? '')],
            'checks' => array_values($checks),
            'waste' => sanitize_text($row['waste'] ?? ''),
            'training' => sanitize_text($row['training'] ?? ''),
            'notes' => sanitize_text($row['notes'] ?? ''),
            'rx' => sanitize_text($row['rx'] ?? ''),
            'participation' => $prev['participation'] ?? false
        ];
        save_user_meta($user['email'], 'checklists', $checklists);
        $message = $date . ' を保存しました';
    }
}

uksort($checklists, fn($a, $b) => strcmp($b, $a));
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>過去ログ編集</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<h1>過去ログ編集</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="notice">最近の記録を降順で表示しています。1日ごとに保存できるので、迷わず少しずつ整えられます。</div>
<div class="stack">
    <?php foreach ($checklists as $d => $e): ?>
        <form method="post" class="card" style="text-align:left;">
            <?= csrf_field(); ?>
            <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;align-items:start;">
                <label>日付<input type="date" name="entry[date]" value="<?= htmlspecialchars($d) ?>"></label>
                <label>処方箋枚数<input type="number" name="entry[rx]" value="<?= htmlspecialchars($e['rx'] ?? '') ?>" style="width:100%;"></label>
                <label style="grid-column:1 / -1;">チェック項目<textarea name="entry[checks]" rows="2" placeholder="改行やカンマで区切り"><?= htmlspecialchars(implode("\n", $e['checks'] ?? [])) ?></textarea></label>
                <label style="grid-column:1 / -1;">補足<textarea name="entry[items]" rows="2" class="muted" placeholder="短く状況を添えます。"> <?= htmlspecialchars($e['items']['text'] ?? '') ?></textarea></label>
                <label>研修<textarea name="entry[training]" rows="2"><?= htmlspecialchars($e['training'] ?? '') ?></textarea></label>
                <label>備考<textarea name="entry[notes]" rows="2"><?= htmlspecialchars($e['notes'] ?? '') ?></textarea></label>
                <label>廃棄<textarea name="entry[waste]" rows="2"><?= htmlspecialchars($e['waste'] ?? '') ?></textarea></label>
            </div>
            <div class="actions" style="justify-content:flex-end;"><button type="submit">この日の記録を保存</button></div>
        </form>
    <?php endforeach; ?>
</div>
</body>
</html>
