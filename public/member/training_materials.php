<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();

$all = read_json('training/materials.json', []);
$message = '';
$memos = load_user_meta($user['email'], 'training_memos');
$notifications = load_user_meta($user['email'], 'notifications');
$usersIndex = [];
foreach (users_all() as $u) {
    $usersIndex[$u['email']] = $u;
}
$accountCache = [];
$ownerInfo = function ($email) use (&$accountCache, $usersIndex) {
    if (!isset($accountCache[$email])) {
        $accountCache[$email] = load_user_meta($email, 'account');
    }
    $acc = $accountCache[$email];
    $owner = $usersIndex[$email] ?? [];
    $name = $owner['name'] ?? $email;
    $pharmacy = $acc['pharmacy_name'] ?? ($owner['pharmacy'] ?? '');
    return [$name, $pharmacy];
};

$normalizeComments = function(string $id, array $list) {
    $changed = false;
    foreach ($list as &$c) {
        if (empty($c['id'])) { $c['id'] = uniqid('c', true); $changed = true; }
        if (!isset($c['parent'])) { $c['parent'] = ''; $changed = true; }
    }
    unset($c);
    if ($changed) {
        json_write_atomic('comments/' . $id . '.json', $list);
    }
    return $list;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
        $title = sanitize_text($_POST['title'] ?? '');
        $desc = sanitize_text($_POST['description'] ?? '');
        $url = sanitize_text($_POST['url'] ?? '');
        $is_public = !empty($_POST['is_public']);
        $id = mb_substr(md5($title . microtime()), 0, 12);
        $fileName = '';
        if (!empty($_FILES['file']['name'])) {
            if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
                $message = '10MBを超えるファイルはアップロードできません';
            } else {
                [$ok, $info, $mime] = validated_upload($_FILES['file']);
                if ($ok) { $fileName = $info; }
                else { $message = $info; }
            }
        }
        if ($message === '') {
            $material = [
                'id' => $id,
                'owner' => $user['email'],
                'title' => $title,
                'description' => $desc,
                'url' => $url,
                'file' => $fileName,
                'public' => $is_public,
                'created' => date('c')
            ];
            $all[] = $material;
            json_write_atomic('training/materials.json', $all);
            $message = '登録しました';
        }
    } elseif ($action === 'update') {
        $id = sanitize_text($_POST['id'] ?? '');
        foreach ($all as &$m) {
            if (($m['id'] ?? '') !== $id) { continue; }
            if ($m['owner'] !== $user['email']) { $message = '編集権限がありません'; break; }
            $title = sanitize_text($_POST['title'] ?? $m['title']);
            $desc = sanitize_text($_POST['description'] ?? $m['description']);
            $url = sanitize_text($_POST['url'] ?? $m['url']);
            $is_public = !empty($_POST['is_public']);
            $fileName = $m['file'] ?? '';
            if (!empty($_POST['remove_file'])) {
                $path = data_path('uploads/' . $fileName);
                if ($fileName && file_exists($path)) { unlink($path); }
                $fileName = '';
            }
            if (!empty($_FILES['file']['name'])) {
                if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
                    $message = '10MBを超えるファイルはアップロードできません';
                } else {
                    [$ok, $info, $mime] = validated_upload($_FILES['file']);
                    if ($ok) {
                        $path = data_path('uploads/' . $fileName);
                        if ($fileName && file_exists($path)) { unlink($path); }
                        $fileName = $info;
                    } else { $message = $info; }
                }
            }
            if ($message === '') {
                $m['title'] = $title;
                $m['description'] = $desc;
                $m['url'] = $url;
                $m['file'] = $fileName;
                $m['public'] = $is_public;
                json_write_atomic('training/materials.json', $all);
                $message = '更新しました';
            }
            break;
        }
        unset($m);
    } elseif ($action === 'delete') {
        $id = sanitize_text($_POST['id'] ?? '');
        $kept = [];
        foreach ($all as $m) {
            if (($m['id'] ?? '') === $id) {
                if ($m['owner'] !== $user['email']) { $kept[] = $m; $message = '削除権限がありません'; continue; }
                if (!empty($m['file'])) {
                    $path = data_path('uploads/' . $m['file']);
                    if (file_exists($path)) { unlink($path); }
                }
                @unlink(data_path('comments/' . $id . '.json'));
                @unlink(data_path('comments/' . $id . '_ratings.json'));
                unset($memos[$id]);
                continue;
            }
            $kept[] = $m;
        }
        $all = $kept;
        save_user_meta($user['email'], 'training_memos', $memos);
        json_write_atomic('training/materials.json', $all);
        if ($message === '') { $message = '削除しました'; }
    } elseif ($action === 'complete') {
        $id = sanitize_text($_POST['id'] ?? '');
        $history = load_user_meta($user['email'], 'training_history');
        $history[] = ['id'=>$id,'date'=>date('Y-m-d')];
        save_user_meta($user['email'], 'training_history', $history);
        $checklists = load_user_meta($user['email'], 'checklists');
        $today = date('Y-m-d');
        $title = '';
        foreach ($all as $m) { if (($m['id'] ?? '') === $id) { $title = $m['title']; break; } }
        $label = $title ? $title . ' (' . $id . ')' : $id;
        $checklists[$today]['training'] = trim(($checklists[$today]['training'] ?? '') . "\n受講: " . $label);
        save_user_meta($user['email'], 'checklists', $checklists);
        $message = '受講を記録しました';
    } elseif ($action === 'comment') {
        $id = sanitize_text($_POST['id'] ?? '');
        $text = sanitize_text($_POST['comment'] ?? '');
        $parent = sanitize_text($_POST['parent'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $commentList = read_json('comments/' . $id . '.json', []);
        $commentList[] = ['id'=>uniqid('c', true),'parent'=>$parent,'user'=>$user['name'],'email'=>$user['email'],'ip'=>$ip,'text'=>$text,'at'=>date('c')];
        json_write_atomic('comments/' . $id . '.json', $commentList);
        foreach ($all as $m) {
            if (($m['id'] ?? '') === $id) {
                $ownerMail = $m['owner'] ?? '';
                if ($ownerMail && $ownerMail !== $user['email']) {
                    $ownerNotes = load_user_meta($ownerMail, 'notifications');
                    $ownerNotes[] = [
                        'type' => 'comment',
                        'title' => $m['title'] ?? '教材',
                        'from' => $user['name'],
                        'material' => $id,
                        'at' => date('c')
                    ];
                    save_user_meta($ownerMail, 'notifications', $ownerNotes);
                }
                break;
            }
        }
        $message = 'コメントしました';
    } elseif ($action === 'rate') {
        $id = sanitize_text($_POST['id'] ?? '');
        $score = (int)($_POST['score'] ?? 0);
        if ($score >=1 && $score <=5) {
            $ratings = read_json('comments/' . $id . '_ratings.json', []);
            $ratings[] = ['user'=>$user['email'],'score'=>$score];
            json_write_atomic('comments/' . $id . '_ratings.json', $ratings);
            $message = '評価しました';
        }
    } elseif ($action === 'memo') {
        $id = sanitize_text($_POST['id'] ?? '');
        $memoText = sanitize_text($_POST['memo'] ?? '');
        $memos[$id] = $memoText;
        save_user_meta($user['email'], 'training_memos', $memos);
        $message = 'メモを保存しました';
    } elseif ($action === 'clear_notifications') {
        $notifications = [];
        save_user_meta($user['email'], 'notifications', $notifications);
        $message = 'お知らせをクリアしました';
    }
}

$q = sanitize_text($_GET['q'] ?? '');
$list = array_filter($all, function($m) use ($user, $q) {
    $match = $q === '' || mb_strpos($m['title'], $q) !== false;
    return ($m['public'] || $m['owner'] === $user['email']) && $match;
});
usort($list, fn($a,$b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));
$history = load_user_meta($user['email'], 'training_history');
$history = array_reverse($history);
$titleIndex = [];
foreach ($all as $material) {
    if (!empty($material['id'])) {
        $titleIndex[$material['id']] = $material['title'] ?? $material['id'];
    }
}
$commentCache = [];
$recentComments = [];
foreach ($list as $material) {
    $comments = $normalizeComments($material['id'], read_json('comments/' . $material['id'] . '.json', []));
    $commentCache[$material['id']] = $comments;
    foreach ($comments as $comment) {
        $recentComments[] = [
            'material' => $material,
            'comment' => $comment,
        ];
    }
}
usort($recentComments, fn($a,$b) => strcmp($b['comment']['at'] ?? '', $a['comment']['at'] ?? ''));
$recentComments = array_slice($recentComments, 0, 5);
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>研修教材</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<div class="layout">
<h1>研修教材</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($notifications)): ?>
    <div class="card highlight">
        <div class="section-title">
            <h2>お知らせ</h2>
            <form method="post" style="margin:0;">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="clear_notifications">
                <button type="submit" class="secondary">既読にする</button>
            </form>
        </div>
        <ul class="muted">
            <?php foreach (array_reverse($notifications) as $note): ?>
                <li><?= htmlspecialchars(substr($note['at'] ?? '',0,16)) ?>：<?= htmlspecialchars($note['title'] ?? '') ?> に <?= htmlspecialchars($note['from'] ?? '') ?> さんがコメントしました。</li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<div class="card highlight">
    <div class="section-title">
        <h2>閲覧→受講で記録に残す</h2>
        <span class="badge">行動心理</span>
    </div>
</div>
<div class="training-layout">
    <div>
        <div class="card">
            <form method="get" class="row-grid" style="align-items:flex-end;">
                <label>スレッド検索<input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="キーワードで検索"></label>
                <button type="submit" class="secondary">検索</button>
            </form>
        </div>
        <?php $threadNo = 1; foreach ($list as $m): list($ownerName, $ownerPharmacy) = $ownerInfo($m['owner']); $isOwner = $m['owner'] === $user['email']; $commentList = $commentCache[$m['id']] ?? []; ?>
        <div class="bbs-thread" id="thread-<?= htmlspecialchars($m['id']) ?>">
            <div class="bbs-head">
                <div class="bbs-title">【<?= htmlspecialchars($ownerPharmacy ?: '無所属') ?>】<?= htmlspecialchars($m['title']) ?></div>
                <div class="bbs-meta">1 ：<?= htmlspecialchars($ownerName) ?>＠<?= htmlspecialchars($ownerPharmacy ?: '薬局') ?> 投稿日：<?= htmlspecialchars(substr($m['created'] ?? '',0,16)) ?> ID:<?= htmlspecialchars(substr($m['id'], -6)) ?> <?= $m['public'] ? '◆公開中' : '◆非公開' ?></div>
            </div>
            <div class="bbs-body">
                <?= nl2br(htmlspecialchars($m['description'] ?: '（説明なし）')) ?>
                <?php if ($m['url']): ?><div>URL: <a href="<?= htmlspecialchars($m['url']) ?>" target="_blank" rel="noopener">リンク</a></div><?php endif; ?>
                <?php if ($m['file']): ?><div><a href="<?= url_for('attachments.php'); ?>?f=<?= urlencode($m['file']) ?>">添付ファイルを閲覧</a></div><?php endif; ?>
            </div>
            <div class="bbs-actions">
                <form method="post" class="inline">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                    <button type="submit">👀→受講</button>
                </form>
                <form method="post" class="inline">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="rate">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                    <label class="inline">★評価<select name="score">
                        <option value="">-</option>
                        <?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                    </select></label>
                    <button type="submit" class="secondary">投稿</button>
                </form>
            </div>
            <?php if ($isOwner): ?>
                <details class="bbs-owner">
                    <summary>◆オーナー編集</summary>
                    <form method="post" enctype="multipart/form-data" class="memo-area">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                        <label>タイトル<input type="text" name="title" value="<?= htmlspecialchars($m['title']) ?>" required></label>
                        <label>説明<textarea name="description" rows="2"><?= htmlspecialchars($m['description']) ?></textarea></label>
                        <label>URL<input type="url" name="url" value="<?= htmlspecialchars($m['url']) ?>"></label>
                        <?php if ($m['file']): ?>
                            <p class="muted">添付: <?= htmlspecialchars($m['file']) ?></p>
                            <label><input type="checkbox" name="remove_file" value="1">添付を削除</label>
                        <?php endif; ?>
                        <label>ファイル差し替え<input type="file" name="file"></label>
                        <label><input type="checkbox" name="is_public" value="1" <?= $m['public'] ? 'checked' : '' ?>>公開する</label>
                        <div class="inline" style="gap:8px; flex-wrap:wrap;">
                            <button type="submit">更新</button>
                        </div>
                    </form>
                    <form method="post" onsubmit="return confirm('削除しますか？');" style="margin-top:10px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                        <button type="submit" class="danger">削除</button>
                    </form>
                </details>
            <?php endif; ?>
            <form method="post" class="bbs-comment-form">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                <input type="hidden" name="parent" value="">
                <textarea name="comment" rows="2" placeholder="名無しの薬局さん：コメントをどうぞ"></textarea>
                <div class="inline" style="justify-content:space-between;width:100%;">
                    <button type="submit" class="secondary">書き込む</button>
                    <small class="muted">IPは自動で添付されます</small>
                </div>
            </form>
            <ul class="bbs-posts">
                <?php $i = 2; foreach ($commentList as $c): ?>
                    <li class="bbs-post" id="comment-<?= htmlspecialchars($c['id']) ?>">
                        <div class="bbs-meta"><?= $i ?> ：<?= htmlspecialchars($c['user']) ?> (<?= htmlspecialchars($c['ip']) ?>) 投稿日：<?= htmlspecialchars(substr($c['at'] ?? '',0,16)) ?> ID:<?= htmlspecialchars(substr($c['id'], -5)) ?> <?= $c['parent'] ? '>>'.$c['parent'] : '' ?></div>
                        <div class="bbs-body"><?= nl2br(htmlspecialchars($c['text'])) ?></div>
                        <form method="post" class="comment-reply-inline">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="comment">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                            <input type="hidden" name="parent" value="<?= htmlspecialchars($c['id']) ?>">
                            <textarea name="comment" rows="1" placeholder="返信"></textarea>
                            <button type="submit" class="secondary">レス</button>
                        </form>
                    </li>
                <?php $i++; endforeach; ?>
            </ul>
            <form method="post" class="memo-area">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="memo">
                <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                <label>非公開メモ<textarea name="memo" rows="2" placeholder="公開には見えないメモを残せます。"><?= htmlspecialchars($memos[$m['id']] ?? '') ?></textarea></label>
                <button type="submit" class="secondary">保存</button>
            </form>
        </div>
        <?php $threadNo++; endforeach; ?>
        <div class="card">
            <h3>受講済み</h3>
            <ul>
                <?php foreach ($history as $h): $title = $titleIndex[$h['id']] ?? $h['id']; ?>
                    <li><?= htmlspecialchars($h['date']) ?> - <?= htmlspecialchars($title) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="sticky">
        <div class="card">
            <h3>教材登録</h3>
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <label>タイトル<input type="text" name="title" required></label>
                <label>説明<textarea name="description" rows="2"></textarea></label>
                <label>URL<input type="url" name="url" placeholder="ファイルなしでも登録可"></label>
                <label>ファイル<input type="file" name="file"></label>
                <label><input type="checkbox" name="is_public" value="1">公開する</label>
                <button type="submit">保存</button>
            </form>
        </div>
        <?php if (!empty($recentComments)): ?>
        <div class="card">
            <h3>新着コメント</h3>
            <ul class="muted">
                <?php foreach ($recentComments as $rc): $c = $rc['comment']; $mat = $rc['material']; ?>
                    <li>
                        <a href="#comment-<?= htmlspecialchars($c['id']) ?>">
                            <?= htmlspecialchars(substr($c['at'] ?? '', 0, 16)) ?>：<?= htmlspecialchars($mat['title'] ?? '教材') ?>（<?= htmlspecialchars($c['user'] ?? '名無し') ?>）
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div class="card">
            <h3>新着一覧</h3>
            <ul class="muted">
                <?php foreach ($list as $mRecent): list($ownerNameRecent, $ownerPharmacyRecent) = $ownerInfo($mRecent['owner']); ?>
                    <li>
                        <a href="#thread-<?= htmlspecialchars($mRecent['id']) ?>">
                            <?= htmlspecialchars(substr($mRecent['created'] ?? '', 0, 10)) ?>：<?= htmlspecialchars($mRecent['title']) ?>（<?= htmlspecialchars($ownerPharmacyRecent ?: '未設定') ?>）
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</div>
</body></html>
