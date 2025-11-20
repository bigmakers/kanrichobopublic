<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$schedule = load_user_meta($user['email'], 'schedule');
if (!is_array($schedule)) { $schedule = []; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = $_POST['events'] ?? [];

    $events = [];
    foreach ($rows as $row) {
        $dateVal = sanitize_text($row['date'] ?? '');
        $titleVal = sanitize_text($row['title'] ?? '');
        $detailVal = sanitize_text($row['detail'] ?? '');
        $participateVal = !empty($row['participate']);

        if ($dateVal === '' && $titleVal === '' && $detailVal === '') {
            continue;
        }

        $events[] = [
            'date' => $dateVal,
            'title' => $titleVal,
            'detail' => $detailVal,
            'participate' => $participateVal,
        ];
    }

    $schedule['events'] = $events;
    save_user_meta($user['email'], 'schedule', $schedule);
}

$storedEvents = $schedule['events'] ?? [];
if (!is_array($storedEvents)) {
    $storedEvents = [];
}
$events = $storedEvents ?: [[]];
$upcoming = array_filter($events, fn($ev) => !empty($ev['date']));
usort($upcoming, function($a, $b) {
    return strcmp($a['date'] ?? '', $b['date'] ?? '');
});
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
        <h2 class="card-title">今日決めて、先回りで行動</h2>
        <p class="subtext">やることを「日付」と「一言メモ」に落とすだけで実行率が変わります。迷ったら、まず1件だけ登録してみてください。</p>
        <div class="hero-actions">
            <a class="btn" href="<?= url_for('member/checklist.php'); ?>">チェックリストと連携</a>
            <button type="button" class="secondary" id="add-row">予定を追加</button>
        </div>
    </div>

    <div class="two-col">
        <form method="post" class="card" id="schedule-form">
            <?= csrf_field(); ?>
            <div class="section-title" style="margin-bottom:16px;">
                <h2>予定リスト</h2>
                <span class="muted">上から順に今日→先の順で並びます</span>
            </div>
            <div id="events">
                <?php foreach ($events as $i=>$e): ?>
                    <div class="event-row">
                        <div class="row-grid">
                            <label>日付<input type="date" name="events[<?= $i ?>][date]" value="<?= htmlspecialchars($e['date'] ?? '') ?>"></label>
                            <label>タイトル<input type="text" name="events[<?= $i ?>][title]" placeholder="勉強会・巡回予定など" value="<?= htmlspecialchars($e['title'] ?? '') ?>"></label>
                            <label class="inline" style="gap:8px;">
                                <input type="checkbox" name="events[<?= $i ?>][participate]" value="1" <?= !empty($e['participate'])?'checked':''; ?>>参加予定
                            </label>
                        </div>
                        <label>内容<textarea name="events[<?= $i ?>][detail]" rows="2" placeholder="場所・共有事項や持ち物メモなどを残せます。">
<?= htmlspecialchars($e['detail'] ?? '') ?></textarea></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <button type="button" class="secondary" id="add-row-bottom">行を追加</button>
                <button type="submit">保存</button>
            </div>
        </form>

        <div class="card col-side sticky">
            <h2 class="card-title">登録済みの予定</h2>
            <?php if ($upcoming): ?>
                <ul class="checks-list">
                    <?php foreach ($upcoming as $ev): ?>
                        <li style="list-style:none; padding:12px; border:1px solid var(--border); border-radius:var(--radius); background:#fff;">
                            <div style="font-weight:700;"><?= htmlspecialchars($ev['date'] ?? '') ?> <?= htmlspecialchars($ev['title'] ?? '予定') ?></div>
                            <?php if (!empty($ev['detail'])): ?><div class="muted"><?= nl2br(htmlspecialchars($ev['detail'])) ?></div><?php endif; ?>
                            <?php if (!empty($ev['participate'])): ?><div class="badge" style="margin-top:6px;">参加記録対象</div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">まだ予定はありません。まずは1件登録してみましょう。</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
let scheduleIndex = <?= (int)count($events); ?>;
const template = () => {
    const idx = scheduleIndex++;
    const wrap = document.createElement('div');
    wrap.className = 'event-row';
    wrap.innerHTML = `
        <div class="row-grid">
            <label>日付<input type="date" name="events[${idx}][date]"></label>
            <label>タイトル<input type="text" name="events[${idx}][title]" placeholder="勉強会・巡回予定など"></label>
            <label class="inline" style="gap:8px;">
                <input type="checkbox" name="events[${idx}][participate]" value="1">参加予定
            </label>
        </div>
        <label>内容<textarea name="events[${idx}][detail]" rows="2" placeholder="場所・共有事項や持ち物メモなどを残せます。"></textarea></label>
    `;
    return wrap;
};
const addButtons = [document.getElementById('add-row'), document.getElementById('add-row-bottom')];
addButtons.forEach(btn => btn && btn.addEventListener('click', () => {
    document.getElementById('events').appendChild(template());
}));
</script>
</body></html>
