<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/sanitize.php';

function users_all() {
    return read_json('users.json', []);
}

function auth_user_by_email($email) {
    $email = strtolower(trim($email));
    $users = users_all();
    foreach ($users as $user) {
        if (($user['email'] ?? '') === $email) return $user;
    }
    return null;
}

function save_user($user) {
    $users = users_all();
    $found = false;
    foreach ($users as &$u) {
        if ($u['email'] === $user['email']) {
            $u = $user;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $users[] = $user;
    }
    json_write_atomic('users.json', $users);
}

function login($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
}

function logout() {
    $_SESSION = [];
    session_destroy();
}

function delete_user($email) {
    $email = strtolower(trim($email));
    $users = users_all();
    $filtered = array_values(array_filter($users, function ($u) use ($email) {
        return ($u['email'] ?? '') !== $email;
    }));
    json_write_atomic('users.json', $filtered);
}
?>
