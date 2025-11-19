<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$rx = load_user_meta($user['email'], 'rx');
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
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>処方箋枚数</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>処方箋枚数</h1>
<?= member_nav(); ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>月<input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()"></label>
    <table class="table">
        <tr><th>日付</th><th>枚数</th></tr>
        <?php foreach ($entries as $i=>$e): ?>
        <tr>
            <td><input type="date" name="date[]" value="<?= htmlspecialchars($e['date']) ?>"></td>
            <td><input type="number" name="count[]" value="<?= htmlspecialchars($e['count']) ?>"></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <button type="submit">保存</button>
</form>
</body></html>
