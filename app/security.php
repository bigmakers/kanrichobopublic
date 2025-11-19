<?php
function configure_session() {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function security_status() {
    return [
        'cookie_httponly' => ini_get('session.cookie_httponly'),
        'use_strict_mode' => ini_get('session.use_strict_mode'),
        'cookie_secure' => ini_get('session.cookie_secure'),
        'cookie_samesite' => ini_get('session.cookie_samesite')
    ];
}

function enforce_login() {
    if (empty($_SESSION['user'])) {
        header('Location: ' . url_for('login.php'));
        exit;
    }
}

function require_admin() {
    if (empty($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function header_no_sniff() {
    header('X-Content-Type-Options: nosniff');
}

function app_base_path() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script === '') {
        return '';
    }
    $dir = str_replace('\\', '/', dirname($script));
    $dir = rtrim($dir, '/');
    // strip member/admin leaf so links resolve to the public root
    $dir = preg_replace('#/(member|admin)$#', '', $dir);
    if ($dir === '/') {
        $dir = '';
    }
    return $dir;
}

function url_for(string $path) {
    $base = app_base_path();
    $prefix = ($base === '/' || $base === '') ? '' : $base;
    return $prefix . '/' . ltrim($path, '/');
}
?>
