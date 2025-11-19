<?php
require_once __DIR__ . '/../app/bootstrap.php';
$logged = !empty($_SESSION['user']);
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>使い方ガイド</title><link rel="stylesheet" href="styles.css"></head>
<body class="mono">
<h1>使い方ガイド</h1>
<?php if ($logged): ?>
<p><a href="/index.php">メンバー機能へ戻る</a></p>
<?php endif; ?>
<div class="card">
    <h3>概要</h3>
    <p>ログイン後、ホームから薬局管理帳簿や研修教材、マイスケジュールへアクセスできます。</p>
    <p>管理・バックアップ関連の説明は管理者画面で確認してください。</p>
</div>
</body></html>
