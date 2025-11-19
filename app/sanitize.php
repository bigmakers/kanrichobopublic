<?php
function sanitize_text($value) {
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$value);
    $value = strip_tags($value);
    return trim($value);
}

function sanitize_array($data) {
    if (!is_array($data)) {
        return sanitize_text($data);
    }
    $clean = [];
    foreach ($data as $k => $v) {
        $clean[$k] = sanitize_array($v);
    }
    return $clean;
}
?>
