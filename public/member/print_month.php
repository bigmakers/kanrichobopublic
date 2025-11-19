<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
$checklists = load_user_meta($user['email'], 'checklists');
$month = sanitize_text($_GET['month'] ?? date('Y-m'));
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>薬局管理帳簿</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="print">
<form method="get" style="margin-bottom:12px;">
    <label>月を選択 <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()"></label>
</form>
<h2>薬局管理帳簿 <?= htmlspecialchars($month) ?>の記録</h2>
<table class="table" style="font-size:12px;">
    <tr><th>日付</th><th>チェック項目</th><th>廃棄</th><th>研修</th><th>備考</th><th>処方箋</th></tr>
    <?php foreach ($checklists as $date=>$entry): if (strpos($date,$month)===0): ?>
    <tr>
        <td><?= htmlspecialchars($date) ?></td>
        <td><?= nl2br(htmlspecialchars($entry['items']['text'] ?? '')) ?></td>
        <td style="width:120px;"><?= nl2br(htmlspecialchars($entry['waste'] ?? '')) ?></td>
        <td><?= nl2br(htmlspecialchars($entry['training'] ?? '')) ?></td>
        <td style="width:160px;"><?= nl2br(htmlspecialchars($entry['notes'] ?? '')) ?></td>
        <td><?= htmlspecialchars($entry['rx'] ?? '') ?></td>
    </tr>
    <?php endif; endforeach; ?>
</table>
<h3>試験検査／不良品処理／安全確保・適正販売／リスク区分・設備点検</h3>
<textarea style="width:100%;height:120px;"></textarea>
</body></html>
