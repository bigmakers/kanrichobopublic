<?php
require_once __DIR__ . '/../app/bootstrap.php';
logout();
header('Location: /login.php');
exit;
?>
