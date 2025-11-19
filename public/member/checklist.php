<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$date = sanitize_text($_GET['date'] ?? date('Y-m-d'));
$checklists = load_user_meta($user['email'], 'checklists');
$todo = load_user_meta($user['email'], 'todo');
$rxCounts = load_user_meta($user['email'], 'rx');
$account = load_user_meta($user['email'], 'account');
$schedule = load_user_meta($user['email'], 'schedule');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = sanitize_text($_POST['date'] ?? $date);
    $entry = [
        'items' => sanitize_array($_POST['items'] ?? []),
        'checks' => array_values(array_filter(array_map('sanitize_text', $_POST['checks'] ?? []))),
        'waste' => sanitize_text($_POST['waste'] ?? ''),
        'training' => sanitize_text($_POST['training'] ?? ''),
        'notes' => sanitize_text($_POST['notes'] ?? ''),
        'rx' => sanitize_text($_POST['rx'] ?? ''),
        'participation' => !empty($_POST['participation'])
    ];
    $checklists[$date] = $entry;
    save_user_meta($user['email'], 'checklists', $checklists);
    for ($i=0;$i<10;$i++) {
        $todo[$i]['text'] = sanitize_text($_POST['todo'][$i]['text'] ?? ($todo[$i]['text'] ?? ''));
        $todo[$i]['done'] = !empty($_POST['todo'][$i]['done']);
        if ($todo[$i]['done']) {
            $todo[$i]['text'] = '';
            $todo[$i]['done'] = false;
        }
    }
    save_user_meta($user['email'], 'todo', $todo);

    // 処方箋枚数を月次集計に同期
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

    $message = '自動保存しました';
}
$entry = $checklists[$date] ?? ['items'=>[],'checks'=>[],'waste'=>'','training'=>'','notes'=>'','rx'=>'','participation'=>false];
$checkItems = $account['check_items'] ?? [];
$dailyChecks = [];
if (!empty($checkItems)) {
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
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>薬局管理帳簿</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<script>
function changeDate(sel){
    if(confirm('保存していない変更は失われます。切り替えますか？')){
        location.href='?date='+sel.value;
    }
}
</script>
</head>
<body class="mono">
<h1>薬局管理帳簿</h1>
<?= member_nav(); ?>
<div class="notice">※各県地方ルールによる記載内容は備考に記録してください</div>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="two-col">
    <?= csrf_field(); ?>
    <div class="card col-main">
        <label>日付<input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="changeDate(this)"></label>
        <div class="field-group">
            <div class="section-title" style="display:flex;justify-content:space-between;align-items:center;">
                <span>チェック項目（ランダム3件）</span>
                <small class="muted">左寄せで並べ、脳が迷わず「はい」を選べる配置です</small>
            </div>
            <div class="checks-list">
                <?php foreach ($dailyChecks as $item): ?>
                    <label class="inline pill"><input type="checkbox" name="checks[]" value="<?= htmlspecialchars($item) ?>" <?= in_array($item, $entry['checks'] ?? []) ? 'checked' : '' ?>><?= htmlspecialchars($item) ?></label>
                <?php endforeach; ?>
            </div>
            <label>自由記入<textarea name="items[text]" rows="3" placeholder="チェックに補足を残すときに使えます。集中が切れない短文が推奨です。 ">
<?= htmlspecialchars($entry['items']['text'] ?? '') ?></textarea></label>
        </div>
        <label>医薬品廃棄<textarea name="waste" rows="2"><?= htmlspecialchars($entry['waste']) ?></textarea></label>
        <label>研修記録<textarea name="training" rows="2"><?= htmlspecialchars($entry['training']) ?></textarea></label>
        <label>備考<textarea name="notes" rows="3"><?= htmlspecialchars($entry['notes']) ?></textarea></label>
        <label>処方箋枚数<input type="number" name="rx" value="<?= htmlspecialchars($entry['rx']) ?>"></label>
        <label><input type="checkbox" name="participation" value="1" <?= !empty($entry['participation'])?'checked':''; ?>>MYスケジュール参加</label>
        <?php if ($todaySchedule): ?>
            <div class="notice">本日のマイスケジュール</div>
            <ul class="muted">
                <?php foreach ($todaySchedule as $ev): ?>
                    <li><?= htmlspecialchars($ev['title'] ?? '予定') ?> <?= htmlspecialchars($ev['detail'] ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions"><button type="submit">保存</button></div>
    </div>
    <div class="card col-side">
        <h3 class="card-title">TODO (10枠)</h3>
        <p class="muted">完了チェック後は次回リロードでクリアされます。</p>
        <?php for($i=0;$i<10;$i++): ?>
            <div class="todo-row">
                <input type="text" name="todo[<?= $i ?>][text]" value="<?= htmlspecialchars($todo[$i]['text'] ?? '') ?>" placeholder="TODO <?= $i+1 ?>">
                <label class="inline"><input type="checkbox" name="todo[<?= $i ?>][done]" value="1">完了</label>
            </div>
        <?php endfor; ?>
    </div>
</form>
<div class="card" style="margin-top:16px;">
    <h3><?= htmlspecialchars($monthPrefix) ?>のログ</h3>
    <p class="muted" style="margin:4px 0;">降順の過去ログ編集は<a href="<?= htmlspecialchars(url_for('member/history.php')) ?>">こちら</a>から。</p>
    <table class="table">
        <tr><th>日付</th><th>チェック</th><th>研修</th><th>備考</th><th>処方箋</th></tr>
        <?php foreach ($monthLog as $d=>$e): ?>
            <tr>
                <td><?= htmlspecialchars($d) ?></td>
                <td style="width:200px;">
                    <?php if (!empty($e['checks'])): ?>
                        <div><?= htmlspecialchars(implode(' / ', $e['checks'])) ?></div>
                    <?php endif; ?>
                    <div class="muted" style="white-space:pre-line;">
                        <?= htmlspecialchars($e['items']['text'] ?? '') ?>
                    </div>
                </td>
                <td style="width:160px;"><?= nl2br(htmlspecialchars($e['training'] ?? '')) ?></td>
                <td style="width:200px;"><?= nl2br(htmlspecialchars($e['notes'] ?? '')) ?></td>
                <td><?= htmlspecialchars($e['rx'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body></html>
