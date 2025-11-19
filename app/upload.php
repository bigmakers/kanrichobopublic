<?php
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/storage.php';

function normalize_filename($name) {
    $name = sanitize_text($name);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return $name ?: 'file';
}

function validated_upload($file, $allowed = []) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'アップロードに失敗しました'];
    }
    $tmp = $file['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    if ($allowed && !in_array($mime, $allowed)) {
        return [false, '許可されていないファイル形式です'];
    }
    $name = normalize_filename($file['name']);
    $targetDir = data_path('uploads');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $target = $targetDir . '/' . uniqid() . '_' . $name;
    move_uploaded_file($tmp, $target);
    return [true, basename($target), $mime];
}
?>
