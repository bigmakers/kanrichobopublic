<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();
csrf_check();
$users = users_all();
$status = security_status();
$alerts = scan_data_security();
$aff = read_json('affiliates.json', ['header' => '']);
$materials = read_json('training/materials.json', []);
$message = '';
$adminCount = count(array_filter($users, fn($u) => $u['is_admin'] ?? false));
$materialTotal = count($materials);
$publicMaterials = count(array_filter($materials, fn($m) => $m['public'] ?? false));
$fileMaterials = count(array_filter($materials, fn($m) => !empty($m['file'])));
$commentFiles = glob(data_path('comments/*.json')) ?: [];
$commentCount = 0;
$ratingCount = 0;
foreach ($commentFiles as $path) {
    $data = json_decode(@file_get_contents($path), true);
    if (!is_array($data)) { continue; }
    if (substr($path, -12) === '_ratings.json') {
        $ratingCount += count($data);
    } else {
        $commentCount += count($data);
    }
}
$checklistFiles = glob(data_path('user_*_checklists.json')) ?: [];
$checkEntryCount = 0;
$lastCheckDate = '';
foreach ($checklistFiles as $file) {
    $json = json_decode(@file_get_contents($file), true);
    if (!is_array($json)) { continue; }
    $checkEntryCount += count($json);
    foreach ($json as $dateKey => $_row) {
        if ($dateKey > $lastCheckDate) { $lastCheckDate = $dateKey; }
    }
}
$lastCheckDate = $lastCheckDate ?: '---';
$latestMaterials = $materials;
usort($latestMaterials, fn($a,$b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));
$latestMaterials = array_slice($latestMaterials, 0, 5);
$recentUsers = $users;
usort($recentUsers, fn($a,$b) => strcmp($b['email'] ?? '', $a['email'] ?? ''));
$recentUsers = array_slice($recentUsers, 0, 5);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html = preg_replace('/[\x00-\x1F\x7F]/u', '', $_POST['header_html'] ?? '');
    json_write_atomic('affiliates.json', ['header' => $html]);
    $aff['header'] = $html;
    $message = 'ヘッダーHTMLを更新しました';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理ダッシュボード</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<div class="layout">
    <h1>管理ダッシュボード</h1>
    <?= admin_nav(); ?>
    <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card highlight">
        <div class="section-title">
            <h2>管理モード ハイライト</h2>
            <span class="badge">全体俯瞰</span>
        </div>
        <p class="subtext">システム全体のKPIと主要アクションを1枚にまとめています。ここから各管理ページへ素早く遷移できます。</p>
        <div class="stat-list">
            <div class="stat">ユーザー <?= htmlspecialchars(count($users)) ?></div>
            <div class="stat">管理者 <?= htmlspecialchars($adminCount) ?></div>
            <div class="stat">教材 <?= htmlspecialchars($materialTotal) ?></div>
            <div class="stat">公開教材 <?= htmlspecialchars($publicMaterials) ?></div>
            <div class="stat">コメント <?= htmlspecialchars($commentCount) ?></div>
            <div class="stat">評価 <?= htmlspecialchars($ratingCount) ?></div>
        </div>
        <p class="muted" style="margin-top:12px;">チェックログ総数 <?= htmlspecialchars($checkEntryCount) ?> / 最終記録日 <?= htmlspecialchars($lastCheckDate) ?></p>
        <div class="hero-actions">
            <a class="btn" href="<?= url_for('admin/users_admin.php'); ?>">ユーザー管理</a>
            <a class="btn secondary" href="<?= url_for('admin/training_admin.php'); ?>">研修教材管理</a>
            <a class="btn secondary" href="<?= url_for('admin/records_admin.php'); ?>">記録管理</a>
            <a class="btn secondary" href="<?= url_for('admin/backup.php'); ?>">バックアップ</a>
        </div>
    </div>

    <div class="grid">
        <div>
            <div class="card">
                <div class="section-title">
                    <h2>ユーザー概要</h2>
                    <span class="badge">直近5件</span>
                </div>
                <p class="subtext">新規登録や権限の確認に使えるよう、メール降順で並べています。</p>
                <table class="table">
                    <tr><th>氏名</th><th>薬局</th><th>権限</th></tr>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name'] ?? $u['email']) ?></td>
                            <td><?= htmlspecialchars($u['pharmacy'] ?? '') ?></td>
                            <td><?= !empty($u['is_admin']) ? '管理者' : '一般' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="actions"><a class="btn secondary" href="<?= url_for('admin/users_admin.php'); ?>">ユーザー一覧へ</a></div>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>記録モニタリング</h2>
                    <span class="badge">帳簿</span>
                </div>
                <p class="subtext">チェックリストファイルから総数と最終更新日を自動集計しています。</p>
                <div class="stat-list">
                    <div class="stat">総記録 <?= htmlspecialchars($checkEntryCount) ?></div>
                    <div class="stat">最終日 <?= htmlspecialchars($lastCheckDate) ?></div>
                    <div class="stat">添付付き教材 <?= htmlspecialchars($fileMaterials) ?></div>
                </div>
                <p class="muted">詳細の削除や調整は <a href="<?= url_for('admin/records_admin.php'); ?>">記録管理</a> から行えます。</p>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>クイックアクション</h2>
                    <span class="badge">ショートカット</span>
                </div>
                <ul>
                    <li><a href="<?= url_for('admin/users_admin.php'); ?>">ユーザーを追加・編集</a></li>
                    <li><a href="<?= url_for('admin/records_admin.php'); ?>">日別チェックリストの整理</a></li>
                    <li><a href="<?= url_for('admin/training_admin.php'); ?>">研修教材の審査/非公開化</a></li>
                    <li><a href="<?= url_for('admin/backup.php'); ?>">全体バックアップをダウンロード</a></li>
                    <li><a href="<?= url_for('guide.php'); ?>">メンバー向けガイドを確認</a></li>
                </ul>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="section-title">
                    <h2>セキュリティステータス</h2>
                    <span class="badge">セッション</span>
                </div>
                <table class="table">
                    <tr><th>HTTPOnly</th><td><?= htmlspecialchars($status['cookie_httponly']) ?></td></tr>
                    <tr><th>Strict-Mode</th><td><?= htmlspecialchars($status['use_strict_mode']) ?></td></tr>
                    <tr><th>Secure</th><td><?= htmlspecialchars($status['cookie_secure']) ?></td></tr>
                    <tr><th>SameSite</th><td><?= htmlspecialchars($status['cookie_samesite']) ?></td></tr>
                </table>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>監査ログ</h2>
                    <span class="badge">data/監視</span>
                </div>
                <?php if ($alerts): ?>
                    <ul>
                        <?php foreach ($alerts as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">問題は検出されていません。</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>研修教材インサイト</h2>
                    <span class="badge">最新<?= htmlspecialchars((string)count($latestMaterials)) ?></span>
                </div>
                <p class="subtext">公開比率 <?= htmlspecialchars((string)($materialTotal ? round($publicMaterials / max(1, $materialTotal) * 100, 1) : 0)) ?>% / 添付あり <?= htmlspecialchars($fileMaterials) ?> 件</p>
                <ul>
                    <?php foreach ($latestMaterials as $m): ?>
                        <li><?= htmlspecialchars($m['title'] ?? '教材') ?> <?= !empty($m['owner']) ? '<span class="muted">(' . htmlspecialchars($m['owner']) . ')</span>' : '' ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($latestMaterials)): ?>
                        <li class="muted">教材はまだ登録されていません。</li>
                    <?php endif; ?>
                </ul>
                <div class="actions"><a class="btn secondary" href="<?= url_for('admin/training_admin.php'); ?>">教材管理へ</a></div>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>ヘッダーアフィリエイトHTML</h2>
                    <span class="badge">全ページに反映</span>
                </div>
                <form method="post">
                    <?= csrf_field(); ?>
                    <textarea name="header_html" rows="4" placeholder="例: バナーHTMLやお知らせを貼り付け"><?= htmlspecialchars($aff['header'] ?? '') ?></textarea>
                    <div class="actions"><button type="submit">更新</button></div>
                </form>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>バックアップ</h2>
                    <span class="badge">推奨</span>
                </div>
                <p class="subtext">研修ファイルを除いた data/ 一式をZIP化してダウンロードできます。週次での取得を推奨します。</p>
                <div class="actions"><a class="btn" href="<?= url_for('admin/backup.php'); ?>">バックアップを作成</a></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
