<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$date = sanitize_text($_GET['date'] ?? date('Y-m-d'));
$checklists = load_user_meta($user['email'], 'checklists');
$todo = load_user_meta($user['email'], 'todo');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = sanitize_text($_POST['date'] ?? $date);
    $entry = [
        'items' => sanitize_array($_POST['items'] ?? []),
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
    $message = '自動保存しました';
}
$entry = $checklists[$date] ?? ['items'=>[],'waste'=>'','training'=>'','notes'=>'','rx'=>'','participation'=>false];
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>薬局管理帳簿</title><link rel="stylesheet" href="../styles.css">
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
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>日付<input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="changeDate(this)"></label>
    <label>チェック項目<textarea name="items[text]" rows="4"><?= htmlspecialchars($entry['items']['text'] ?? '') ?></textarea></label>
    <label>医薬品廃棄<textarea name="waste" rows="2"><?= htmlspecialchars($entry['waste']) ?></textarea></label>
    <label>研修記録<textarea name="training" rows="2"><?= htmlspecialchars($entry['training']) ?></textarea></label>
    <label>備考<textarea name="notes" rows="3"><?= htmlspecialchars($entry['notes']) ?></textarea></label>
    <label>処方箋枚数<input type="number" name="rx" value="<?= htmlspecialchars($entry['rx']) ?>"></label>
    <label><input type="checkbox" name="participation" value="1" <?= !empty($entry['participation'])?'checked':''; ?>>MYスケジュール参加</label>
    <h3>TODO (10枠)</h3>
    <?php for($i=0;$i<10;$i++): ?>
        <div>
            <input type="text" name="todo[<?= $i ?>][text]" value="<?= htmlspecialchars($todo[$i]['text'] ?? '') ?>" placeholder="TODO <?= $i+1 ?>">
            <label><input type="checkbox" name="todo[<?= $i ?>][done]" value="1">完了</label>
        </div>
    <?php endfor; ?>
    <button type="submit">保存</button>
</form>
</body></html>
