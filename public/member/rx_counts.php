<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$rx = load_user_meta($user['email'], 'rx');
$checklists = load_user_meta($user['email'], 'checklists');
$month = sanitize_text($_GET['month'] ?? date('Y-m'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = sanitize_text($_POST['month'] ?? $month);
    $dates = $_POST['date'] ?? [];
    $counts = $_POST['count'] ?? [];
    $entries = [];
    $total = 0;
    foreach ($dates as $i=>$d) {
        $c = (int)$counts[$i];
        $entries[] = ['date'=>sanitize_text($d),'count'=>$c];
        $total += $c;
    }
    $rx[$month] = ['entries'=>$entries,'total'=>$total];
    save_user_meta($user['email'], 'rx', $rx);
}
$entries = $rx[$month]['entries'] ?? [['date'=>date('Y-m-d'),'count'=>0]];
foreach ($checklists as $d => $e) {
    if (strpos($d, $month) === 0 && isset($e['rx']) && $e['rx'] !== '') {
        $found = false;
        foreach ($entries as &$row) {
            if ($row['date'] === $d) { $row['count'] = (int)$e['rx']; $found = true; break; }
        }
        if (!$found) { $entries[] = ['date'=>$d,'count'=>(int)$e['rx']]; }
    }
}
usort($entries, fn($a,$b) => strcmp($a['date'],$b['date']));
$total = $rx[$month]['total'] ?? array_sum(array_column($entries, 'count'));
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>処方箋枚数</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<h1>処方箋枚数</h1>
<?= member_nav(); ?>
<div class="notice">日次チェックで入力した枚数が自動で反映されます。ここでは月ごとに整えて合計を確認できます。</div>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <div class="row-grid" style="align-items:flex-end;">
        <label>月<input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()"></label>
        <div class="stat">
            <div class="muted">合計</div>
            <strong style="font-size:20px;"><?= htmlspecialchars($total) ?>枚</strong>
        </div>
    </div>
    <table class="table">
        <tr><th>日付</th><th>枚数</th></tr>
        <?php foreach ($entries as $i=>$e): ?>
        <tr>
            <td><input type="date" name="date[]" value="<?= htmlspecialchars($e['date']) ?>"></td>
            <td><input type="number" name="count[]" value="<?= htmlspecialchars($e['count']) ?>"></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="actions">
        <button type="button" class="secondary" onclick="addRow()">行を追加</button>
        <button type="submit">保存</button>
    </div>
</form>
<script>
function addRow(){
    const table=document.querySelector('table');
    const row=document.createElement('tr');
    row.innerHTML='<td><input type="date" name="date[]" value="<?= htmlspecialchars($month) ?>-01"></td><td><input type="number" name="count[]" value="0"></td>';
    table.appendChild(row);
}
</script>
</body></html>
