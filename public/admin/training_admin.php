<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
$materials = read_json('training/materials.json', []);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_text($_POST['title'] ?? '');
    $owner = sanitize_text($_POST['owner'] ?? '');
    $url = sanitize_text($_POST['url'] ?? '');
    $id = mb_substr(md5($title . microtime()), 0, 12);
    $materials[] = ['id'=>$id,'owner'=>$owner,'title'=>$title,'url'=>$url,'public'=>!empty($_POST['public'])];
    json_write_atomic('training/materials.json', $materials);
    $message = '追加しました';
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>研修教材管理</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>研修教材管理</h1>
<?= admin_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>タイトル<input type="text" name="title"></label>
    <label>所有者メール<input type="email" name="owner"></label>
    <label>URL<input type="url" name="url"></label>
    <label><input type="checkbox" name="public" value="1">公開</label>
    <button type="submit">追加</button>
</form>
<div class="card">
    <h3>登録済み</h3>
    <ul>
        <?php foreach ($materials as $m): ?><li><?= htmlspecialchars($m['id']) ?> - <?= htmlspecialchars($m['title']) ?> (<?= htmlspecialchars($m['owner']) ?>)</li><?php endforeach; ?>
    </ul>
</div>
</body></html>
