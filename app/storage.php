<?php
require_once __DIR__ . '/sanitize.php';

function data_path($file) {
    return __DIR__ . '/../data/' . $file;
}

function read_json($file, $default = []) {
    $path = data_path($file);
    if (!file_exists($path)) return $default;
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : $default;
}

function json_write_atomic($file, $data) {
    $path = data_path($file);
    $tmp = $path . '.tmp';
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($tmp, $json, LOCK_EX);
    if (file_exists($path)) {
        unlink($path);
    }
    rename($tmp, $path);
}

function append_log($file, $entry) {
    $data = read_json($file, []);
    $data[] = $entry;
    json_write_atomic($file, $data);
}
?>
