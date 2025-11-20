<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
header('Location: ' . url_for('member/training.php'));
exit;
