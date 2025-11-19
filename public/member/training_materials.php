<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$all = read_json('training/materials.json', []);
$comments = read_json('comments/index.json', []);
$message = '';

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
        $checklists[$today]['training'] = trim(($checklists[$today]['training'] ?? '') . "\n受講: " . $id);
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
    }
}
$q = sanitize_text($_GET['q'] ?? '');
$list = array_filter($all, function($m) use ($user, $q) {
    $match = $q === '' || mb_strpos($m['title'], $q) !== false;
    return ($m['public'] || $m['owner'] === $user['email']) && $match;
});
$history = load_user_meta($user['email'], 'training_history');
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>研修教材</title><link rel="stylesheet" href="/styles.css"></head>
<body class="mono">
<h1>研修教材</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
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
<div class="card">
    <form method="get">
        <label>タイトル検索<input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></label>
        <button type="submit">検索</button>
    </form>
</div>
<?php foreach ($list as $m): ?>
<div class="card">
    <h3><?= htmlspecialchars($m['title']) ?></h3>
    <p><?= nl2br(htmlspecialchars($m['description'])) ?></p>
    <?php if ($m['url']): ?><p>URL: <a href="<?= htmlspecialchars($m['url']) ?>" target="_blank">リンク</a></p><?php endif; ?>
    <?php if ($m['file']): ?><p><a href="/attachments.php?f=<?= urlencode($m['file']) ?>">添付をダウンロード</a></p><?php endif; ?>
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
    <form method="post">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="comment">
        <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
        <label>コメント<textarea name="comment" rows="2"></textarea></label>
        <button type="submit">コメント</button>
    </form>
    <?php $commentList = read_json('comments/' . $m['id'] . '.json', []); ?>
    <div class="notice">コメント</div>
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
</body></html>
