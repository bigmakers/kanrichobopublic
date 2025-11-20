<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();

// データの読み書き（教材データ + コメント）
$trainingData = load_user_meta($user['email'], 'training');
if (!is_array($trainingData)) {
    $trainingData = [];
}

if (empty($trainingData['materials'])) {
    $trainingData['materials'] = [
        ['id' => 't1', 'title' => '新人研修マニュアル', 'desc' => '基本的な調剤フローと接遇について', 'link' => '#'],
        ['id' => 't2', 'title' => 'ハイリスク薬一覧', 'desc' => '注意すべき薬剤と服薬指導のポイント', 'link' => '#'],
        ['id' => 't3', 'title' => '今月の新薬情報', 'desc' => 'メーカー勉強会資料のまとめ', 'link' => '#'],
    ];
}
if (empty($trainingData['comments'])) {
    $trainingData['comments'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'post_comment') {
        $matId = sanitize_text($_POST['material_id'] ?? '');
        $text = sanitize_text($_POST['comment'] ?? '');
        if ($matId && $text) {
            $trainingData['comments'][] = [
                'material_id' => $matId,
                'user' => $user['name'],
                'text' => $text,
                'date' => date('Y-m-d H:i')
            ];
            save_user_meta($user['email'], 'training', $trainingData);
        }
    }
    // PRGパターンでリロード対策
    header('Location: ' . url_for('member/training_materials.php'));
    exit;
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
    /* 教材カードグリッド */
    .materials-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
    }
    .material-card {
        background: #fff; border: 1px solid var(--border); border-radius: 8px;
        display: flex; flex-direction: column; overflow: hidden;
        transition: transform 0.2s;
    }
    .material-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: var(--primary); }
    
    .mat-header { background: #f9fafb; padding: 16px; border-bottom: 1px solid var(--border); }
    .mat-title { font-weight: bold; font-size: 1.1rem; color: var(--primary); margin-bottom: 4px; display:block; }
    .mat-desc { font-size: 0.9rem; color: var(--text-muted); }
    
    .mat-body { padding: 16px; flex: 1; background: #fff; }
    
    /* コメントエリア（チャット風） */
    .comments-section { margin-top: 16px; border-top: 1px dashed var(--border); padding-top: 10px; }
    .comment-list { max-height: 150px; overflow-y: auto; margin-bottom: 10px; font-size: 0.85rem; }
    .comment-item { background: #f3f4f6; padding: 8px; border-radius: 6px; margin-bottom: 6px; }
    .comment-meta { font-weight: bold; font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="container">
    <h1>研修教材・資料共有</h1>
    <?= member_nav(); ?>

    <div class="materials-grid">
        <?php foreach ($trainingData['materials'] as $mat): ?>
            <div class="material-card">
                <div class="mat-header">
                    <a href="<?= htmlspecialchars($mat['link']) ?>" class="mat-title" target="_blank">
                        📄 <?= htmlspecialchars($mat['title']) ?>
                    </a>
                    <div class="mat-desc"><?= htmlspecialchars($mat['desc']) ?></div>
                </div>
                
                <div class="mat-body">
                    <div style="font-weight:bold; font-size:0.9rem; margin-bottom:8px;">💬 コミュニケーション</div>
                    
                    <div class="comment-list">
                        <?php 
                        $relatedComments = array_filter($trainingData['comments'], fn($c) => ($c['material_id'] ?? '') === $mat['id']);
                        if (empty($relatedComments)): ?>
                            <div style="color:#ccc; text-align:center; padding:10px;">コメントはまだありません</div>
                        <?php else: ?>
                            <?php foreach ($relatedComments as $c): ?>
                                <div class="comment-item">
                                    <div class="comment-meta">
                                        <span><?= htmlspecialchars($c['user']) ?></span>
                                        <span><?= htmlspecialchars(substr($c['date'], 5, 11)) ?></span>
                                    </div>
                                    <div style="margin-top:2px;">&gt; <?= nl2br(htmlspecialchars($c['text'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="post" style="display:flex; gap:8px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="post_comment">
                        <input type="hidden" name="material_id" value="<?= htmlspecialchars($mat['id']) ?>">
                        <input type="text" name="comment" placeholder="質問やメモを入力..." style="font-size:0.85rem; padding:6px;" required>
                        <button type="submit" class="btn" style="padding:6px 12px; font-size:0.85rem;">送信</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="material-card" style="border-style:dashed; justify-content:center; align-items:center; color:var(--text-muted); min-height:200px; cursor:pointer;" onclick="alert('教材追加機能は管理者が行います（デモ）')">
            <div style="font-size:2rem;">+</div>
            <div>新しい教材を追加</div>
        </div>
    </div>
</div>
</body>
</html>
