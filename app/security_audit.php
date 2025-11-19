<?php
require_once __DIR__ . '/storage.php';

function scan_data_security() {
    $alerts = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../data', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $alerts[] = 'PHPファイルがdata配下に存在: ' . $file->getFilename();
        }
        if ($file->isFile() && $file->getSize() < 5 * 1024 * 1024) {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'password') !== false) {
                $alerts[] = '平文パスワードの可能性: ' . $file->getFilename();
            }
        }
    }
    return $alerts;
}
?>
