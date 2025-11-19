<?php
require_once __DIR__ . '/../app/bootstrap.php';
enforce_login();
header_no_sniff();
$file = basename($_GET['f'] ?? '');
$path = data_path('uploads/' . $file);
if (!$file || !file_exists($path)) { http_response_code(404); exit('not found'); }
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path);
finfo_close($finfo);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($path);
exit;
?>
