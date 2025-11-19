<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$data = load_user_meta($user['email'], 'pharmacists');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize_text($_POST['name'] ?? ''),
        'license' => sanitize_text($_POST['license'] ?? ''),
        'insurance' => sanitize_text($_POST['insurance'] ?? ''),
        'jpa' => sanitize_text($_POST['jpa'] ?? ''),
        'kakari' => sanitize_text($_POST['kakari'] ?? ''),
        'training' => sanitize_text($_POST['training'] ?? ''),
        'note' => sanitize_text($_POST['note'] ?? '')
    ];
    save_user_meta($user['email'], 'pharmacists', $data);
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>薬剤師名簿</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>薬剤師・登録販売者名簿</h1>
<?= member_nav(); ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <label>氏名<input type="text" name="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>"></label>
    <label>免許番号<input type="text" name="license" value="<?= htmlspecialchars($data['license'] ?? '') ?>"></label>
    <label>保険薬剤師登録番号<input type="text" name="insurance" value="<?= htmlspecialchars($data['insurance'] ?? '') ?>"></label>
    <label>日薬番号<input type="text" name="jpa" value="<?= htmlspecialchars($data['jpa'] ?? '') ?>"></label>
    <label>かかりつけ薬剤師届出<input type="text" name="kakari" value="<?= htmlspecialchars($data['kakari'] ?? '') ?>"></label>
    <label>研修修了証番号<input type="text" name="training" value="<?= htmlspecialchars($data['training'] ?? '') ?>"></label>
    <label>備考<textarea name="note" rows="2"><?= htmlspecialchars($data['note'] ?? '') ?></textarea></label>
    <button type="submit">保存</button>
</form>
<div class="card">
    <h3>カード表示</h3>
    <p><strong><?= htmlspecialchars($data['name'] ?? '') ?></strong></p>
    <p>免許: <?= htmlspecialchars($data['license'] ?? '') ?></p>
    <p>保険: <?= htmlspecialchars($data['insurance'] ?? '') ?></p>
    <p>日薬: <?= htmlspecialchars($data['jpa'] ?? '') ?></p>
    <p>かかりつけ薬剤師届出: <?= htmlspecialchars($data['kakari'] ?? '') ?></p>
    <p>研修修了証番号: <?= htmlspecialchars($data['training'] ?? '') ?></p>
    <p>備考: <?= htmlspecialchars($data['note'] ?? '') ?></p>
</div>
</body></html>
