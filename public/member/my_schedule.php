<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$schedule = load_user_meta($user['email'], 'schedule');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $events = [];
    foreach ($_POST['events']['date'] ?? [] as $i=>$d) {
        $events[] = [
            'date' => sanitize_text($d),
            'title' => sanitize_text($_POST['events']['title'][$i] ?? ''),
            'detail' => sanitize_text($_POST['events']['detail'][$i] ?? ''),
            'participate' => !empty($_POST['events']['participate'][$i])
        ];
    }
    $schedule['events'] = $events;
    save_user_meta($user['email'], 'schedule', $schedule);
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>マイスケジュール</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>マイスケジュール</h1>
<?= member_nav(); ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <div id="events">
        <?php foreach ($schedule['events'] ?? [[]] as $i=>$e): ?>
            <div class="card" style="background:#0f0f0f;">
                <label>日付<input type="date" name="events[date][]" value="<?= htmlspecialchars($e['date'] ?? '') ?>"></label>
                <label>タイトル<input type="text" name="events[title][]" value="<?= htmlspecialchars($e['title'] ?? '') ?>"></label>
                <label>内容<textarea name="events[detail][]" rows="2"><?= htmlspecialchars($e['detail'] ?? '') ?></textarea></label>
                <label><input type="checkbox" name="events[participate][<?= $i ?>]" value="1" <?= !empty($e['participate'])?'checked':''; ?>>参加</label>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit">保存</button>
</form>
</body></html>
