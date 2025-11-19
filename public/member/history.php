<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$checklists = load_user_meta($user['email'], 'checklists');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = [];
    foreach ($_POST['entry'] ?? [] as $row) {
        $date = sanitize_text($row['date'] ?? '');
        if ($date === '') { continue; }
        $checksRaw = sanitize_text($row['checks'] ?? '');
        $checks = array_filter(array_map('trim', preg_split('/[,\n]+/u', $checksRaw)));
        $updated[$date] = [
            'items' => ['text' => sanitize_text($row['items'] ?? '')],
            'checks' => array_values($checks),
            'waste' => sanitize_text($row['waste'] ?? ''),
            'training' => sanitize_text($row['training'] ?? ''),
            'notes' => sanitize_text($row['notes'] ?? ''),
            'rx' => sanitize_text($row['rx'] ?? ''),
            'participation' => !empty($row['participation'])
        ];
    }
    // preserve dates not posted if any
    $checklists = $updated + array_diff_key($checklists, $updated);
    save_user_meta($user['email'], 'checklists', $checklists);
    $message = '過去ログを更新しました';
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
<div class="notice">最近の記録を降順で表示しています。左寄せの入力と短文でサッと編集できます。</div>
<form method="post">
    <?= csrf_field(); ?>
    <div class="card">
        <table class="table">
            <tr><th>日付</th><th>チェック項目</th><th>研修</th><th>備考</th><th>廃棄</th><th>処方箋</th><th>MY参加</th></tr>
            <?php foreach ($checklists as $d => $e): ?>
                <tr>
                    <td style="white-space:nowrap;"><input type="date" name="entry[][date]" value="<?= htmlspecialchars($d) ?>"></td>
                    <td>
                        <textarea name="entry[][checks]" rows="2" placeholder="改行やカンマで区切り"><?= htmlspecialchars(implode("\n", $e['checks'] ?? [])) ?></textarea>
                        <textarea name="entry[][items]" rows="2" placeholder="補足メモ" class="muted" style="margin-top:4px;"><?= htmlspecialchars($e['items']['text'] ?? '') ?></textarea>
                    </td>
                    <td><textarea name="entry[][training]" rows="2"><?= htmlspecialchars($e['training'] ?? '') ?></textarea></td>
                    <td><textarea name="entry[][notes]" rows="2"><?= htmlspecialchars($e['notes'] ?? '') ?></textarea></td>
                    <td><textarea name="entry[][waste]" rows="2"><?= htmlspecialchars($e['waste'] ?? '') ?></textarea></td>
                    <td><input type="number" name="entry[][rx]" value="<?= htmlspecialchars($e['rx'] ?? '') ?>" style="width:100px;"></td>
                    <td style="text-align:center;"><input type="checkbox" name="entry[][participation]" value="1" <?= !empty($e['participation'])?'checked':''; ?>></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="actions"><button type="submit">一括保存</button></div>
    </div>
</form>
</body>
</html>
