<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/sanitize.php';

function user_key($email) {
    return preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($email));
}

function load_user_meta($email, $name) {
    $file = 'user_' . user_key($email) . '_' . $name . '.json';
    return read_json($file, []);
}

function save_user_meta($email, $name, $data) {
    $file = 'user_' . user_key($email) . '_' . $name . '.json';
    json_write_atomic($file, $data);
}
?>
