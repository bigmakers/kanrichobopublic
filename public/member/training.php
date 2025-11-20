<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();

$trainingData = load_user_meta($user['email'], 'training');
if (!is_array($trainingData)) {
    $trainingData = [];
}
if (empty($trainingData['materials'])) {
    $trainingData['materials'] = [];
}
if (empty($trainingData['comments'])) {
    $trainingData['comments'] = [];
}

$error = '';

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_material') {
        $title = sanitize_text($_POST['title'] ?? '');
        $desc = sanitize_text($_POST['desc'] ?? '');
        $link = sanitize_text($_POST['link'] ?? '');

        if ($title === '') {
            $error = 'タイトルを入力してください';
        }

        $newMaterial = [
            'id' => uniqid('mat_'),
            'title' => $title,
            'desc' => $desc,
            'date' => date('Y-m-d'),
            'uploader' => $user['name'] ?? 'ユーザー',
            'type' => 'link',
            'path' => $link,
        ];

        if (!empty($_FILES['file']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
            finfo_close($finfo);
            $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/gif'];
            if (!in_array($mime, $allowed, true)) {
                $error = 'PDF または画像ファイルのみアップロードできます';
            } else {
                $safeName = normalize_filename($_FILES['file']['name']);
                $target = $uploadDir . '/' . uniqid('up_') . '_' . $safeName;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                    $newMaterial['type'] = 'file';
                    $newMaterial['path'] = 'uploads/' . basename($target);
                } else {
                    $error = 'ファイルのアップロードに失敗しました';
                }
            }
        }

        if (!$error && $newMaterial['type'] === 'link' && $newMaterial['path'] === '') {
            $error = 'ファイルまたはリンクURLを入力してください';
        }

        if (!$error) {
            array_unshift($trainingData['materials'], $newMaterial);
            save_user_meta($user['email'], 'training', $trainingData);
        }
    }

    if ($action === 'post_comment') {
        $matId = sanitize_text($_POST['material_id'] ?? '');
        $text = sanitize_text($_POST['comment'] ?? '');
        if ($matId && $text) {
            $trainingData['comments'][] = [
                'material_id' => $matId,
                'user' => $user['name'] ?? 'ユーザー',
                'text' => $text,
                'date' => date('Y-m-d H:i'),
            ];
            save_user_meta($user['email'], 'training', $trainingData);
        }
    }

    if ($action === 'delete_material') {
        $targetId = sanitize_text($_POST['material_id'] ?? '');
        $trainingData['materials'] = array_values(array_filter($trainingData['materials'], fn($m) => ($m['id'] ?? '') !== $targetId));
        save_user_meta($user['email'], 'training', $trainingData);
    }

    header('Location: ' . url_for('member/training.php'));
    exit;
}

function material_url(array $mat): string {
    $path = $mat['path'] ?? '#';
    if (($mat['type'] ?? '') === 'link' && preg_match('#^https?://#', $path)) {
        return $path;
    }
    return url_for($path);
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>研修教材</title>
<link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<style>
    /* カードグリッド */
    .materials-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 20px; }
    .material-card { background: #fff; border: 1px solid var(--border); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; position: relative; box-shadow: var(--shadow-sm); }
    .material-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: #dbeafe; }
    .mat-header { background: #f9fafb; padding: 16px; border-bottom: 1px solid var(--border); }
    .mat-meta { font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between; margin-bottom: 4px; }
    .mat-title { font-weight: bold; font-size: 1.1rem; color: var(--primary); text-decoration: none; display: block; margin-bottom: 4px; }
    .mat-desc { font-size: 0.9rem; color: var(--text-muted); }
    .mat-body { padding: 16px; flex: 1; background: #fff; display: flex; flex-direction: column; }
    .file-badge { display: inline-block; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #e5e7eb; color: #374151; font-weight: bold; margin-right: 6px; }
    .comment-list { flex: 1; max-height: 150px; overflow-y: auto; margin-bottom: 10px; font-size: 0.85rem; border-top: 1px dashed #e5e7eb; padding-top: 10px; margin-top: 10px; }
    .comment-item { background: #f3f4f6; padding: 8px; border-radius: 6px; margin-bottom: 6px; }
    .btn-del-mat { position: absolute; top: 10px; right: 10px; background: transparent; border: none; color: #9ca3af; cursor: pointer; }
    .btn-del-mat:hover { color: var(--danger); }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal-card { background: #fff; padding: 24px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: var(--shadow-lg); }
</style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h1>研修教材・資料共有</h1>
        <div class="hero-actions">
            <button onclick="openModal()" class="btn">+ 教材を追加</button>
        </div>
    </div>
    <?= member_nav(); ?>

    <?php if ($error): ?>
        <div class="alert" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="materials-grid">
        <?php if (empty($trainingData['materials'])): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--text-muted);">
                登録されている教材はありません。右上のボタンから追加してください。
            </div>
        <?php else: ?>
            <?php foreach ($trainingData['materials'] as $mat): ?>
                <div class="material-card">
                    <form method="post" onsubmit="return confirm('この教材を削除しますか？');">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_material">
                        <input type="hidden" name="material_id" value="<?= htmlspecialchars($mat['id']) ?>">
                        <button type="submit" class="btn-del-mat" title="削除">×</button>
                    </form>

                    <div class="mat-header">
                        <div class="mat-meta">
                            <span><?= htmlspecialchars($mat['date'] ?? '') ?></span>
                            <span>UP: <?= htmlspecialchars($mat['uploader'] ?? '') ?></span>
                        </div>
                        <a href="<?= htmlspecialchars(material_url($mat)) ?>" class="mat-title" target="_blank" rel="noopener">
                            <?php if(($mat['type'] ?? '') === 'file'): ?>
                                <span class="file-badge">FILE</span>
                            <?php else: ?>
                                <span class="file-badge">LINK</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($mat['title']) ?>
                        </a>
                        <div class="mat-desc"><?= nl2br(htmlspecialchars($mat['desc'] ?? '')) ?></div>
                    </div>
                    
                    <div class="mat-body">
                        <div style="font-weight:bold; font-size:0.9rem;">💬 コメント</div>
                        <div class="comment-list">
                            <?php 
                            $relatedComments = array_filter($trainingData['comments'], fn($c) => ($c['material_id'] ?? '') === ($mat['id'] ?? ''));
                            if (empty($relatedComments)): ?>
                                <div style="color:#ccc; padding:4px;">まだコメントはありません</div>
                            <?php else: ?>
                                <?php foreach ($relatedComments as $c): ?>
                                    <div class="comment-item">
                                        <div class="comment-meta">
                                            <strong style="color:#555;"><?= htmlspecialchars($c['user'] ?? '') ?></strong>
                                            <span><?= htmlspecialchars(substr($c['date'] ?? '', 5, 11)) ?></span>
                                        </div>
                                        <div><?= nl2br(htmlspecialchars($c['text'] ?? '')) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="post" style="display:flex; gap:8px;">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="post_comment">
                            <input type="hidden" name="material_id" value="<?= htmlspecialchars($mat['id']) ?>">
                            <input type="text" name="comment" placeholder="コメント..." style="flex:1; padding:6px; border:1px solid #ddd; border-radius:4px;" required>
                            <button type="submit" class="btn secondary" style="padding:6px 12px; font-size:0.8rem;">送信</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="addModal" class="modal-overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal-card">
        <h3>新しい教材を登録</h3>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="add_material">
            
            <label>タイトル <span style="color:red">*</span></label>
            <input type="text" name="title" required placeholder="資料名など">
            
            <label>説明</label>
            <textarea name="desc" rows="2" placeholder="内容の要約"></textarea>
            
            <div style="margin: 16px 0; padding: 16px; background: #f9fafb; border-radius: 8px;">
                <label>ファイルアップロード (PDF/画像など)</label>
                <input type="file" name="file" accept="application/pdf,image/*">
                <div style="text-align:center; margin:10px 0; color:#aaa;">- または -</div>
                <label>リンクURL (WEB記事など)</label>
                <input type="url" name="link" placeholder="https://...">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn secondary" onclick="closeModal()">キャンセル</button>
                <button type="submit" class="btn">登録する</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() { document.getElementById('addModal').style.display = 'flex'; }
function closeModal() { document.getElementById('addModal').style.display = 'none'; }
</script>
</body>
</html>
