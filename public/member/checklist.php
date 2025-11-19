<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();

$date = sanitize_text($_GET['date'] ?? date('Y-m-d'));
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

$checklists = load_user_meta($user['email'], 'checklists');
$todo = load_user_meta($user['email'], 'todo');

$normalizeTodo = function(array $todos): array {
    $packed = [];
    foreach ($todos as $row) {
        $text = trim($row['text'] ?? '');
        if ($text === '') { continue; }
        $packed[] = ['text' => $text, 'done' => !empty($row['done'])];
    }
    $max = 10;
    while (count($packed) < $max) {
        $packed[] = ['text' => '', 'done' => false];
    }
    return array_slice($packed, 0, $max);
};
$todo = $normalizeTodo($todo);

$rxCounts = load_user_meta($user['email'], 'rx');
$account = load_user_meta($user['email'], 'account');
$schedule = load_user_meta($user['email'], 'schedule');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = sanitize_text($_POST['date'] ?? $date);

    if (($_POST['action'] ?? '') === 'save_todo') {
        for ($i = 0; $i < 10; $i++) {
            $todo[$i]['text'] = sanitize_text($_POST['todo'][$i]['text'] ?? ($todo[$i]['text'] ?? ''));
            $todo[$i]['done'] = !empty($_POST['todo'][$i]['done']);
            if ($todo[$i]['done']) {
                $todo[$i]['text'] = '';
                $todo[$i]['done'] = false;
            }
        }
        $todo = $normalizeTodo($todo);
        save_user_meta($user['email'], 'todo', $todo);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    $selectedChecks = array_values(array_filter(array_map('sanitize_text', $_POST['checks'] ?? [])));
    $scheduleChecksPosted = array_values(array_filter(array_map('sanitize_text', $_POST['schedule_checks'] ?? [])));
    $existingEntry = $checklists[$date] ?? ['items'=>[],'checks'=>[],'waste'=>'','training'=>'','notes'=>'','rx'=>'','participation'=>false];

    $entry = [
        'items' => sanitize_array($_POST['items'] ?? []),
        'checks' => array_values(array_unique(array_merge($selectedChecks, $scheduleChecksPosted))),
        'waste' => sanitize_text($_POST['waste'] ?? ''),
        'training' => sanitize_text($_POST['training'] ?? ''),
        'notes' => sanitize_text($_POST['notes'] ?? ''),
        'rx' => sanitize_text($_POST['rx'] ?? ''),
        'participation' => $existingEntry['participation'] ?? false
    ];
    $checklists[$date] = $entry;
    save_user_meta($user['email'], 'checklists', $checklists);

    for ($i = 0; $i < 10; $i++) {
        $todo[$i]['text'] = sanitize_text($_POST['todo'][$i]['text'] ?? ($todo[$i]['text'] ?? ''));
        $todo[$i]['done'] = !empty($_POST['todo'][$i]['done']);
        if ($todo[$i]['done']) {
            $todo[$i]['text'] = '';
            $todo[$i]['done'] = false;
        }
    }
    $todo = $normalizeTodo($todo);
    save_user_meta($user['email'], 'todo', $todo);

    $monthKey = substr($date, 0, 7);
    $rxEntries = $rxCounts[$monthKey]['entries'] ?? [];
    $rxEntries = array_values(array_filter($rxEntries, fn($row) => ($row['date'] ?? '') !== $date));
    $rxVal = (int)($entry['rx'] ?? 0);
    $rxEntries[] = ['date' => $date, 'count' => $rxVal];
    usort($rxEntries, fn($a, $b) => strcmp($a['date'], $b['date']));
    $rxCounts[$monthKey] = [
        'entries' => $rxEntries,
        'total' => array_sum(array_column($rxEntries, 'count'))
    ];
    save_user_meta($user['email'], 'rx', $rxCounts);

    $message = '保存しました';
}

$entry = $checklists[$date] ?? ['items'=>[],'checks'=>[],'waste'=>'','training'=>'','notes'=>'','rx'=>'','participation'=>false];
$checkItems = array_values(array_filter($account['check_items'] ?? []));
$dailyChecks = [];
if (!empty($entry['checks'])) {
    foreach ($entry['checks'] as $saved) {
        if (in_array($saved, $checkItems, true)) {
            $dailyChecks[] = $saved;
        }
    }
    $dailyChecks = array_values(array_unique($dailyChecks));
}
if (empty($dailyChecks) && !empty($checkItems)) {
    $keys = array_rand($checkItems, min(3, count($checkItems)));
    if (!is_array($keys)) { $keys = [$keys]; }
    foreach ($keys as $k) {
        $dailyChecks[] = $checkItems[$k];
    }
}

$monthPrefix = substr($date, 0, 7);
$monthLog = [];
foreach ($checklists as $d => $e) {
    if (strpos($d, $monthPrefix) === 0) {
        $monthLog[$d] = $e;
    }
}
ksort($monthLog);

$todaySchedule = [];
foreach (($schedule['events'] ?? []) as $ev) {
    if (($ev['date'] ?? '') === $date) {
        $todaySchedule[] = $ev;
    }
}
$scheduleCheckOptions = [];
foreach ($todaySchedule as $ev) {
    $label = '参加: ' . trim($ev['title'] ?? '予定');
    if (!empty($ev['detail'])) {
        $label .= ' / ' . $ev['detail'];
    }
    $scheduleCheckOptions[] = $label;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>薬局管理帳簿</title>
<link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<style>
    :root {
        --primary: #2563eb;
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border: #e5e7eb;
        --radius: 8px;
        --shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        margin: 0;
        padding: 20px;
        line-height: 1.5;
    }
    h1 { font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-main); }
    .container { max-width: 1200px; margin: 0 auto; }
    .layout-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .layout-grid { grid-template-columns: 1fr; }
    }
    .card {
        background: var(--bg-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border);
    }
    .card-title { margin-top: 0; font-size: 1.1rem; border-bottom: 2px solid var(--bg-body); padding-bottom: 10px; margin-bottom: 15px; }
    label { display: block; margin-bottom: 12px; font-weight: bold; font-size: 0.9rem; }
    textarea, input[type="text"], input[type="number"], input[type="date"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 16px;
        box-sizing: border-box;
        font-family: inherit;
    }
    textarea:focus, input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    textarea { resize: vertical; min-height: 80px; }
    .date-nav {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        padding: 10px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        justify-content: space-between;
    }
    .date-nav a {
        text-decoration: none;
        color: var(--text-muted);
        padding: 5px 12px;
        border-radius: 4px;
        background: #f9fafb;
        border: 1px solid var(--border);
        font-size: 0.9rem;
    }
    .date-nav a:hover { background: #e5e7eb; }
    .date-nav input[type="date"] { width: auto; border: none; font-weight: bold; font-size: 1.1rem; text-align: center; }
    .checks-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .check-option { position: relative; cursor: pointer; margin: 0; }
    .check-option input { position: absolute; opacity: 0; width: 0; height: 0; }
    .check-option span {
        display: block;
        padding: 8px 16px;
        background: #f3f4f6;
        border-radius: 20px;
        color: var(--text-main);
        transition: all 0.2s;
        border: 1px solid transparent;
        user-select: none;
    }
    .check-option input:checked + span {
        background: #eff6ff;
        color: var(--primary);
        border-color: var(--primary);
        font-weight: bold;
    }
    .todo-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        background: #fff;
        padding: 2px;
    }
    .todo-row input[type="text"] {
        border: none;
        border-bottom: 1px solid var(--border);
        border-radius: 0;
        padding: 8px 0;
    }
    .todo-row input[type="text"]:focus { border-bottom-color: var(--primary); box-shadow: none; }
    .todo-check { transform: scale(1.2); margin-right: 5px; cursor: pointer; }
    .todo-row.is-done input[type="text"] { text-decoration: line-through; color: #9ca3af; }
    .table-responsive { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .table th, .table td { border: 1px solid var(--border); padding: 10px; text-align: left; vertical-align: top; }
    .table th { background: #f9fafb; font-weight: 600; white-space: nowrap; }
    .toast {
        position: fixed; bottom: 20px; right: 20px;
        background: #10b981; color: white;
        padding: 10px 20px; border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        opacity: 0; transition: opacity 0.3s; pointer-events: none;
        z-index: 100;
    }
    .toast.show { opacity: 1; }
    .btn-save {
        background: var(--primary); color: white; border: none;
        padding: 12px 24px; border-radius: 6px; font-size: 1rem; font-weight: bold;
        cursor: pointer; width: 100%;
    }
    .btn-save:hover { opacity: 0.9; }
</style>
<script>
function confirmNav(url){
    location.href = url;
}
</script>
</head>
<body>
<div class="container">
    <h1>薬局管理帳簿</h1>
    <?= member_nav(); ?>
    <p style="color:#6b7280;font-size:0.9rem;">※各県地方ルールによる記載内容は備考に記録してください</p>

    <div id="toast" class="toast"></div>

    <div class="date-nav">
        <a href="?date=<?= $prevDate ?>" onclick="return confirm('保存していない内容は破棄されます。移動しますか？');">← 前日</a>
        <form method="get" style="margin:0;">
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="if(confirm('日付を変更しますか？')) this.form.submit()">
        </form>
        <a href="?date=<?= $nextDate ?>" onclick="return confirm('保存していない内容は破棄されます。移動しますか？');">翌日 →</a>
    </div>

    <?php if ($message): ?>
        <div style="background:#d1fae5; color:#065f46; padding:10px; border-radius:6px; margin-bottom:20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="layout-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">

        <div class="col-main">
            <div class="card">
                <div class="card-title">本日のチェック</div>
                <div class="checks-list">
                    <?php foreach ($dailyChecks as $item): ?>
                        <label class="check-option">
                            <input type="checkbox" name="checks[]" value="<?= htmlspecialchars($item) ?>" <?= in_array($item, $entry['checks'] ?? []) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($item) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if ($scheduleCheckOptions): ?>
                    <div style="margin-top:16px; font-weight:bold; font-size:0.85rem; color:var(--text-muted); margin-bottom:8px;">スケジュール参加</div>
                    <div class="checks-list">
                        <?php foreach ($scheduleCheckOptions as $opt): ?>
                            <label class="check-option">
                                <input type="checkbox" name="schedule_checks[]" value="<?= htmlspecialchars($opt) ?>" <?= in_array($opt, $entry['checks'] ?? []) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($opt) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label style="margin-top:16px;">チェック補足・自由記入
                    <textarea name="items[text]" placeholder="例：〇〇確認済み、△△は在庫切れのため未実施"><?= htmlspecialchars($entry['items']['text'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="card">
                <div class="card-title">業務記録</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label>処方箋枚数 (枚)</label>
                        <input type="number" name="rx" value="<?= htmlspecialchars($entry['rx']) ?>" style="font-size:1.2rem; font-weight:bold;">
                    </div>
                    <div></div>
                </div>
                <div style="margin-top:16px;">
                    <label>医薬品廃棄</label>
                    <textarea name="waste" rows="2"><?= htmlspecialchars($entry['waste']) ?></textarea>
                </div>
                <div style="margin-top:16px;">
                    <label>研修記録</label>
                    <textarea name="training" rows="2"><?= htmlspecialchars($entry['training']) ?></textarea>
                </div>
                <div style="margin-top:16px;">
                    <label>備考・引き継ぎ</label>
                    <textarea name="notes" rows="3"><?= htmlspecialchars($entry['notes']) ?></textarea>
                </div>
                <div style="margin-top:24px;">
                    <button type="submit" class="btn-save">日報を保存する</button>
                </div>
            </div>
        </div>

        <div class="col-side">
            <div class="card" style="position:sticky; top:20px;">
                <div class="card-title" style="display:flex; justify-content:space-between;">
                    TODO <small style="font-weight:normal; color:var(--text-muted);">自動保存</small>
                </div>
                <div id="todo-list">
                    <?php for($i=0;$i<10;$i++): ?>
                        <?php $isDone = !empty($todo[$i]['done']); ?>
                        <div class="todo-row <?= $isDone ? 'is-done' : '' ?>">
                            <input type="checkbox" class="todo-check" name="todo[<?= $i ?>][done]" value="1" <?= $isDone ? 'checked' : '' ?>>
                            <input type="text" name="todo[<?= $i ?>][text]" value="<?= htmlspecialchars($todo[$i]['text'] ?? '') ?>" placeholder="タスク <?= $i+1 ?>">
                        </div>
                    <?php endfor; ?>
                </div>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:10px;">
                    ※完了チェックした項目は、次回画面を開いた際にクリアされます。
                </p>
            </div>
        </div>
    </form>

    <div class="card" style="margin-top:16px;">
        <h3><?= htmlspecialchars($monthPrefix) ?>のログ</h3>
        <p style="color:var(--text-muted); font-size:0.9rem;">
            過去ログ編集は<a href="<?= htmlspecialchars(url_for('member/history.php')) ?>">こちら</a>
        </p>
        <div class="table-responsive">
            <table class="table">
                <colgroup>
                    <col style="width: 100px;">
                    <col>
                    <col>
                    <col style="width: 80px;">
                </colgroup>
                <thead>
                    <tr><th>日付</th><th>チェック / 補足</th><th>研修 / 備考</th><th>枚数</th></tr>
                </thead>
                <tbody>
                <?php foreach ($monthLog as $d=>$e): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('m/d', strtotime($d))) ?> <small>(<?= ['日','月','火','水','木','金','土'][date('w', strtotime($d))] ?>)</small></td>
                        <td>
                            <?php if (!empty($e['checks'])): ?>
                                <div style="margin-bottom:4px;">
                                    <?php foreach($e['checks'] as $c): ?>
                                        <span style="display:inline-block; background:#e5e7eb; padding:2px 6px; border-radius:4px; font-size:0.8rem; margin-right:2px; margin-bottom:2px;"><?= htmlspecialchars($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div style="white-space:pre-wrap; color:var(--text-muted);"><?= htmlspecialchars($e['items']['text'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if(!empty($e['training'])): ?>
                                <div style="margin-bottom:4px;"><strong>[研]</strong> <?= nl2br(htmlspecialchars($e['training'])) ?></div>
                            <?php endif; ?>
                            <div><?= nl2br(htmlspecialchars($e['notes'] ?? '')) ?></div>
                        </td>
                        <td style="text-align:right; font-weight:bold;">
                            <?= htmlspecialchars($e['rx'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}
const todoContainer = document.getElementById('todo-list');
const todoInputs = todoContainer.querySelectorAll('input[type="text"], input[type="checkbox"]');
let todoTimer = null;
async function saveTodo() {
    const form = document.querySelector('form.layout-grid');
    if (!form) return;
    const fd = new FormData();
    fd.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);
    fd.append('action', 'save_todo');
    fd.append('date', form.querySelector('input[name="date"]').value);
    document.querySelectorAll('.col-side .todo-row').forEach((row, idx) => {
        const text = row.querySelector('input[type="text"]').value;
        const done = row.querySelector('input[type="checkbox"]').checked;
        fd.append(`todo[${idx}][text]`, text);
        if (done) { fd.append(`todo[${idx}][done]`, '1'); }
    });
    try {
        const res = await fetch(location.href, {method:'POST', body:fd});
        if (res.ok) {
            showToast('TODOを保存しました');
        }
    } catch (e) {
        console.error(e);
    }
}
function debounceSaveTodo() {
    clearTimeout(todoTimer);
    todoTimer = setTimeout(saveTodo, 800);
}
todoInputs.forEach(el => {
    if (el.type === 'checkbox') {
        el.addEventListener('change', function() {
            const row = this.closest('.todo-row');
            if(this.checked) row.classList.add('is-done');
            else row.classList.remove('is-done');
            saveTodo();
        });
    } else {
        el.addEventListener('input', debounceSaveTodo);
    }
});
</script>
</body>
</html>
