<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip->open($tmp, ZipArchive::CREATE);
    $dir = realpath(__DIR__ . '/../../data');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (strpos($file->getPathname(), 'uploads') !== false || strpos($file->getPathname(), 'training') !== false) continue;
        $local = substr($file->getPathname(), strlen($dir)+1);
        $zip->addFile($file->getPathname(), $local);
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="backup.zip"');
    readfile($tmp);
    unlink($tmp);
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>バックアップ</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<h1>バックアップ</h1>
<?= admin_nav(); ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <p>data配下をZIP化します（研修ファイル除外）。</p>
    <button type="submit">バックアップをダウンロード</button>
</form>
</body></html>
