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
$events = $schedule['events'] ?? [[]];
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイスケジュール</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<div class="layout">
    <h1>マイスケジュール</h1>
    <?= member_nav(); ?>

    <div class="card highlight">
        <div class="section-title">
            <h2>次の一歩を先に決める</h2>
            <span class="badge">行動を先延ばししない</span>
        </div>
        <p class="subtext">「日付を入れておく」だけでも実行率が上がります。まずは直近の予定を1件書き出しましょう。</p>
        <div class="hero-actions">
            <a class="btn" href="<?= url_for('member/checklist.php'); ?>">チェックリストと連携</a>
            <button type="button" class="secondary" id="add-row">予定を追加</button>
        </div>
    </div>

    <form method="post" class="card">
        <?= csrf_field(); ?>
        <div id="events">
            <?php foreach ($events as $i=>$e): ?>
                <div class="event-row">
                    <div class="row-grid">
                        <label>日付<input type="date" name="events[date][]" value="<?= htmlspecialchars($e['date'] ?? '') ?>"></label>
                        <label>タイトル<input type="text" name="events[title][]" placeholder="勉強会・巡回予定など" value="<?= htmlspecialchars($e['title'] ?? '') ?>"></label>
                        <label class="inline" style="gap:8px;">
                            <input type="checkbox" name="events[participate][<?= $i ?>]" value="1" <?= !empty($e['participate'])?'checked':''; ?>>参加予定
                        </label>
                    </div>
                    <label>内容<textarea name="events[detail][]" rows="2" placeholder="場所・共有事項や持ち物メモなどを残せます。"><?= htmlspecialchars($e['detail'] ?? '') ?></textarea></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <button type="button" class="secondary" id="add-row-bottom">行を追加</button>
            <button type="submit">保存</button>
        </div>
    </form>
</div>
<script>
const template = () => {
    const wrap = document.createElement('div');
    wrap.className = 'event-row';
    wrap.innerHTML = `
        <div class="row-grid">
            <label>日付<input type="date" name="events[date][]"></label>
            <label>タイトル<input type="text" name="events[title][]" placeholder="勉強会・巡回予定など"></label>
            <label class="inline" style="gap:8px;">
                <input type="checkbox" name="events[participate][${Date.now()}]" value="1">参加予定
            </label>
        </div>
        <label>内容<textarea name="events[detail][]" rows="2" placeholder="場所・共有事項や持ち物メモなどを残せます。"></textarea></label>
    `;
    return wrap;
};
const addButtons = [document.getElementById('add-row'), document.getElementById('add-row-bottom')];
addButtons.forEach(btn => btn && btn.addEventListener('click', () => {
    document.getElementById('events').appendChild(template());
}));
</script>
</body></html>
