<?php
require_once __DIR__ . '/../app/bootstrap.php';
logout();
header('Location: ' . url_for('login.php'));
exit;
?>
