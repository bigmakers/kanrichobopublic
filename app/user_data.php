<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/auth.php';

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

function delete_user_records($email) {
    $key = user_key($email);
    foreach (glob(data_path('user_' . $key . '_*.json')) as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }

    $materials = read_json('training/materials.json', []);
    $keep = [];
    foreach ($materials as $m) {
        if (($m['owner'] ?? '') === $email) {
            if (!empty($m['file'])) {
                $uploadPath = data_path('uploads/' . $m['file']);
                if (is_file($uploadPath)) {
                    unlink($uploadPath);
                }
            }
            $commentPath = data_path('comments/' . $m['id'] . '.json');
            $ratingPath = data_path('comments/' . $m['id'] . '_ratings.json');
            if (is_file($commentPath)) unlink($commentPath);
            if (is_file($ratingPath)) unlink($ratingPath);
        } else {
            $commentFile = 'comments/' . $m['id'] . '.json';
            $ratingFile = 'comments/' . $m['id'] . '_ratings.json';
            $comments = read_json($commentFile, []);
            $filteredComments = array_values(array_filter($comments, fn($c) => ($c['email'] ?? '') !== $email));
            if ($filteredComments !== $comments) {
                json_write_atomic($commentFile, $filteredComments);
            }
            $ratings = read_json($ratingFile, []);
            $filteredRatings = array_values(array_filter($ratings, fn($r) => ($r['user'] ?? '') !== $email));
            if ($filteredRatings !== $ratings) {
                json_write_atomic($ratingFile, $filteredRatings);
            }
            $keep[] = $m;
        }
    }
    json_write_atomic('training/materials.json', $keep);

    delete_user($email);
}
?>
