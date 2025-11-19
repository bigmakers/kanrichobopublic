<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();

$all = read_json('training/materials.json', []);
$message = '';
$memos = load_user_meta($user['email'], 'training_memos');
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
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $commentList = read_json('comments/' . $id . '.json', []);
        $commentList[] = ['user'=>$user['name'],'email'=>$user['email'],'ip'=>$ip,'text'=>$text,'at'=>date('c')];
        json_write_atomic('comments/' . $id . '.json', $commentList);
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
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>研修教材</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<div class="layout">
<h1>研修教材</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="card highlight">
    <div class="section-title">
        <h2>今日は1本だけ学ぶ</h2>
        <span class="badge">行動心理: 選択を減らす</span>
    </div>
    <p class="subtext">登録フォームを右にまとめました。左で気になる教材を選び、右で「受講」を押すだけの導線にしています。</p>
</div>
<div class="training-layout">
    <div>
        <div class="card">
            <form method="get" class="row-grid" style="align-items:flex-end;">
                <label>タイトル検索<input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="学びたいテーマで検索"></label>
                <button type="submit" class="secondary">検索</button>
            </form>
        </div>
        <?php foreach ($list as $m): list($ownerName, $ownerPharmacy) = $ownerInfo($m['owner']); ?>
        <div class="card">
            <div class="section-title">
                <h3><?= htmlspecialchars($m['title']) ?></h3>
                <span class="badge"><?= htmlspecialchars($m['public'] ? '公開' : '非公開') ?></span>
            </div>
            <div class="owner-info">薬局: <?= htmlspecialchars($ownerPharmacy ?: '未設定') ?> / 投稿者: <?= htmlspecialchars($ownerName) ?></div>
            <p><?= nl2br(htmlspecialchars($m['description'])) ?></p>
            <?php if ($m['url']): ?><p>URL: <a href="<?= htmlspecialchars($m['url']) ?>" target="_blank">リンク</a></p><?php endif; ?>
            <?php if ($m['file']): ?><p><a href="<?= url_for('attachments.php'); ?>?f=<?= urlencode($m['file']) ?>">添付をダウンロード</a></p><?php endif; ?>
            <div class="grid-2" style="margin-top:10px;gap:10px;align-items:start;">
                <form method="post">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                    <button type="submit">受講</button>
                </form>
                <form method="post">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="rate">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                    <label>評価<select name="score">
                        <option value="">選択</option>
                        <?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= str_repeat('★',$i) ?></option><?php endfor; ?>
                    </select></label>
                    <button type="submit">送信</button>
                </form>
            </div>
            <form method="post" style="margin-top:10px;">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                <label>コメント<textarea name="comment" rows="2"></textarea></label>
                <button type="submit" class="secondary">コメント</button>
            </form>
            <form method="post" class="memo-area" style="margin-top:10px;">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="memo">
                <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                <label>非公開メモ<textarea name="memo" rows="2" placeholder="自分だけの学びメモを残せます。"><?= htmlspecialchars($memos[$m['id']] ?? '') ?></textarea></label>
                <button type="submit" class="secondary">メモを保存</button>
            </form>
            <?php $commentList = read_json('comments/' . $m['id'] . '.json', []); ?>
            <div class="notice" style="margin-top:8px;">コメント</div>
            <ul>
                <?php foreach ($commentList as $c): ?>
                    <li><?= htmlspecialchars($c['user']) ?> (<?= htmlspecialchars($c['ip']) ?>): <?= htmlspecialchars($c['text']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
        <div class="card">
            <h3>受講済み</h3>
            <ul>
                <?php foreach ($history as $h): ?>
                    <li><?= htmlspecialchars($h['date']) ?> - <?= htmlspecialchars($h['id']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="sticky">
        <div class="card highlight">
            <h3>登録を右に集約</h3>
            <p class="subtext">学ぶ→受講→記録の流れを邪魔しない位置に配置しました。思い立った瞬間に登録できます。</p>
        </div>
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
    </div>
</div>
</div>
</body></html>
