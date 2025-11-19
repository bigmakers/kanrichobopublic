<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip->open($tmp, ZipArchive::CREATE);
    $dir = realpath(__DIR__ . '/../../data');
    $prefix = 'user_' . user_key($user['email']) . '_';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (strpos($file->getFilename(), $prefix) !== 0) continue;
        if (strpos($file->getPathname(), 'uploads') !== false || strpos($file->getPathname(), 'training') !== false) continue;
        $local = substr($file->getPathname(), strlen($dir)+1);
        $zip->addFile($file->getPathname(), $local);
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="mydata.zip"');
    readfile($tmp);
    unlink($tmp);
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>個人バックアップ</title><link rel="stylesheet" href="../styles.css"></head>
<body class="mono">
<h1>個人バックアップ</h1>
<?= member_nav(); ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <p>自身のデータのみバックアップ（研修ファイル除外）</p>
    <button type="submit">ダウンロード</button>
</form>
</body></html>
