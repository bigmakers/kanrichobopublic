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

function migrate_user_email($oldEmail, $newEmail) {
    $oldKey = user_key($oldEmail);
    $newKey = user_key($newEmail);
    foreach (glob(data_path('user_' . $oldKey . '_*.json')) as $f) {
        $base = basename($f);
        $newBase = str_replace('user_' . $oldKey . '_', 'user_' . $newKey . '_', $base);
        rename($f, data_path($newBase));
    }

    $materials = read_json('training/materials.json', []);
    $changed = false;
    foreach ($materials as &$m) {
        if (($m['owner'] ?? '') === $oldEmail) {
            $m['owner'] = $newEmail;
            $changed = true;
        }
    }
    if ($changed) {
        json_write_atomic('training/materials.json', $materials);
    }

    foreach (glob(data_path('comments/*.json')) as $commentFile) {
        $comments = read_json('comments/' . basename($commentFile), []);
        $updated = false;
        foreach ($comments as &$c) {
            if (($c['email'] ?? '') === $oldEmail) {
                $c['email'] = $newEmail;
                $updated = true;
            }
        }
        if ($updated) {
            json_write_atomic('comments/' . basename($commentFile), $comments);
        }
    }

    foreach (glob(data_path('comments/*_ratings.json')) as $ratingFile) {
        $ratings = read_json('comments/' . basename($ratingFile), []);
        $updated = false;
        foreach ($ratings as &$r) {
            if (($r['user'] ?? '') === $oldEmail) {
                $r['user'] = $newEmail;
                $updated = true;
            }
        }
        if ($updated) {
            json_write_atomic('comments/' . basename($ratingFile), $ratings);
        }
    }
}
?>
