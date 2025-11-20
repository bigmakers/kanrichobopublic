<?php
require_once __DIR__ . '/../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
$account = load_user_meta($user['email'], 'account');
$todo = load_user_meta($user['email'], 'todo');
$schedules = load_user_meta($user['email'], 'schedule');
$rx = load_user_meta($user['email'], 'rx');
$materials = read_json('training/materials.json', []);
$history = load_user_meta($user['email'], 'training_history');
$completedIds = array_column($history, 'id');
usort($materials, fn($a,$b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));
$latestMaterial = $materials[0] ?? null;
$suggestPool = array_filter($materials, function($m) use ($completedIds, $user) {
    return !in_array($m['id'], $completedIds) && ($m['public'] ?? false || ($m['owner'] ?? '') === $user['email']);
});
shuffle($suggestPool);
$randomMaterials = array_slice($suggestPool, 0, 5);
$todoItems = array_values(array_filter($todo ?? [], function ($row) {
    return trim($row['text'] ?? '') !== '';
}));
$aff = read_json('affiliates.json', ['header' => '']);
$isAdmin = !empty($user['is_admin']);
$adminSnapshot = [
    'user_total' => 0,
    'admin_total' => 0,
    'material_total' => 0,
    'public_material_total' => 0,
    'check_entry_total' => 0,
    'last_check_date' => '---',
    'latest_materials' => []
];
if ($isAdmin) {
    $allUsers = users_all();
    $adminSnapshot['user_total'] = count($allUsers);
    $adminSnapshot['admin_total'] = count(array_filter($allUsers, fn($u) => $u['is_admin'] ?? false));
    $adminSnapshot['material_total'] = count($materials);
    $adminSnapshot['public_material_total'] = count(array_filter($materials, fn($m) => $m['public'] ?? false));
    $files = glob(data_path('user_*_checklists.json')) ?: [];
    $lastCheck = '';
    foreach ($files as $file) {
        $json = json_decode(@file_get_contents($file), true);
        if (!is_array($json)) { continue; }
        $adminSnapshot['check_entry_total'] += count($json);
        foreach ($json as $dateKey => $row) {
            if ($dateKey > $lastCheck) { $lastCheck = $dateKey; }
        }
    }
    $adminSnapshot['last_check_date'] = $lastCheck ?: '---';
    $adminSnapshot['latest_materials'] = array_slice($materials, 0, 3);
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>薬局管理帳簿ウェブシステム</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
</head>
<body class="mono">
<div class="layout">
    <?= $aff['header'] ?? '' ?>
    <h1>薬局管理帳簿ウェブシステム</h1>
    <?= member_nav(); ?>

    <div class="card highlight">
        <div class="section-title">
            <h2>今日の最初の一手</h2>
            <span class="badge">行動心理: 最重要を先に</span>
        </div>
        <p class="subtext">大事なものを先に置くと実行率が上がります。まずは今日のチェックを終わらせ、ついでに今月の数値も記録しましょう。</p>
        <div class="hero-actions">
            <a class="btn" href="<?= url_for('member/checklist.php'); ?>">日次チェックをつける</a>
            <a class="btn secondary" href="<?= url_for('member/rx_counts.php'); ?>">今月の処方箋枚数を記録</a>
            <a class="btn secondary" href="<?= url_for('member/my_schedule.php'); ?>">予定を1件入れる</a>
            <?php if ($isAdmin): ?>
                <a class="btn secondary" href="<?= url_for('admin/index.php'); ?>">管理画面へ</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
        <div class="card highlight">
            <div class="section-title">
                <h2>管理モードダイジェスト</h2>
                <span class="badge">管理者専用</span>
            </div>
            <p class="subtext">ログイン直後に「いまの全体像」と管理メニューの導線をまとめました。数字を確認してそのまま管理画面へ遷移できます。</p>
            <div class="stat-list">
                <div class="stat">ユーザー <?= htmlspecialchars($adminSnapshot['user_total']) ?></div>
                <div class="stat">管理者 <?= htmlspecialchars($adminSnapshot['admin_total']) ?></div>
                <div class="stat">教材 <?= htmlspecialchars($adminSnapshot['material_total']) ?></div>
                <div class="stat">公開教材 <?= htmlspecialchars($adminSnapshot['public_material_total']) ?></div>
                <div class="stat">チェックログ <?= htmlspecialchars($adminSnapshot['check_entry_total']) ?></div>
                <div class="stat">最終記録日 <?= htmlspecialchars($adminSnapshot['last_check_date']) ?></div>
            </div>
            <?php if (!empty($adminSnapshot['latest_materials'])): ?>
                <p class="muted" style="margin-top:12px;">直近の教材登録</p>
                <ul>
                    <?php foreach ($adminSnapshot['latest_materials'] as $mat): ?>
                        <li><?= htmlspecialchars($mat['title'] ?? '教材') ?> <?= !empty($mat['owner']) ? ' / ' . htmlspecialchars($mat['owner']) : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="hero-actions">
                <a class="btn" href="<?= url_for('admin/index.php'); ?>">管理ダッシュボードへ</a>
                <a class="btn secondary" href="<?= url_for('admin/users_admin.php'); ?>">ユーザー管理</a>
                <a class="btn secondary" href="<?= url_for('admin/training_admin.php'); ?>">研修教材管理</a>
                <a class="btn secondary" href="<?= url_for('admin/records_admin.php'); ?>">記録管理</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div>
            <div class="card">
                <div class="section-title">
                    <h2>優先タスク</h2>
                    <span class="badge">迷いを減らす</span>
                </div>
                <p class="subtext">よく使う機能を上にまとめました。迷わず押せる導線で「やるべきこと」から手を付けられます。</p>
                <ul>
                    <li><a href="<?= url_for('member/checklist.php'); ?>">日次チェックリスト</a> — 今日の漏れを防ぐ</li>
                    <li><a href="<?= url_for('member/rx_counts.php'); ?>">月次処方箋枚数</a> — 5分で数字を残す</li>
                    <li><a href="<?= url_for('member/my_schedule.php'); ?>">マイスケジュール編集</a> — 直近を1件入力</li>
                    <li><a href="<?= url_for('member/training.php'); ?>">研修教材</a> — 受講済みを記録</li>
                    <li><a target="_blank" rel="noopener" href="<?= url_for('member/print_month.php'); ?>">印刷ビュー</a> — A4で確認</li>
                </ul>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>アカウント概要</h2>
                    <span class="badge">把握しやすく</span>
                </div>
                <div class="stat-list">
                    <div class="stat">氏名: <?= htmlspecialchars($user['name'] ?? '') ?></div>
                    <div class="stat">薬局: <?= htmlspecialchars($user['pharmacy'] ?? '') ?></div>
                    <div class="stat">許可期限: <?= htmlspecialchars($account['permit_expiry'] ?? '') ?></div>
                    <div class="stat">施設基準: <?= htmlspecialchars(implode(' / ', $account['facilities'] ?? [])) ?></div>
                    <div class="stat">公費枠: <?= htmlspecialchars(implode(' / ', $account['public_frames'] ?? [])) ?></div>
                    <div class="stat">保険薬局/機関コード: <?= htmlspecialchars($account['insurance_code'] ?? '') ?></div>
                    <div class="stat">PMDAメディナビ: <?= htmlspecialchars($account['pmda'] ?? '') ?></div>
                </div>
                <p class="muted">詳細の更新は <a href="<?= url_for('member/account.php'); ?>">アカウント設定</a> から行えます。</p>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="section-title">
                    <h2>TODOリスト</h2>
                    <span class="badge">終わりを見せる</span>
                </div>
                <?php if (count($todoItems) === 0): ?>
                    <p class="muted">未登録です。<a href="<?= url_for('member/checklist.php'); ?>">チェックリスト</a>から追加できます。</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($todoItems as $row): ?>
                            <li><?= htmlspecialchars($row['text'] ?? '') ?> <?= !empty($row['done']) ? '✅' : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>マイスケジュール予定</h2>
                    <span class="badge">先の見通し</span>
                </div>
                <?php if (empty($schedules['events'])): ?>
                    <p class="muted">予定が登録されていません。<a href="<?= url_for('member/my_schedule.php'); ?>">マイスケジュール</a>から追加できます。</p>
                <?php else: ?>
                    <ul>
                        <?php foreach (($schedules['events'] ?? []) as $event): ?>
                            <li><?= htmlspecialchars($event['date'] ?? '') ?> - <?= htmlspecialchars($event['title'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>研修教材ピック</h2>
                    <span class="badge">学びのスイッチ</span>
                </div>
                <?php if ($latestMaterial): ?>
                    <p class="helper" style="margin-bottom:8px;">最新: <strong><?= htmlspecialchars($latestMaterial['title']) ?></strong></p>
                <?php else: ?>
                    <p class="muted">まだ教材がありません。<a href="<?= url_for('member/training.php'); ?>">教材を登録</a>してみましょう。</p>
                <?php endif; ?>
                <?php if ($randomMaterials): ?>
                    <ul>
                        <?php foreach ($randomMaterials as $m): ?>
                            <li><?= htmlspecialchars($m['title'] ?? '教材') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="muted" style="margin-top:6px;">未受講のものをランダムに5件ピックアップしています。</p>
                <?php endif; ?>
                <div class="actions"><a class="btn secondary" href="<?= url_for('member/training.php'); ?>">教材へ進む</a></div>
            </div>

            <div class="card">
                <div class="section-title">
                    <h2>今月の処方箋枚数</h2>
                    <span class="badge">数字で振り返る</span>
                </div>
                <?php if (!empty($rx[date('Y-m')]['total'])): ?>
                    <p class="helper">今月の登録枚数: <strong><?= htmlspecialchars($rx[date('Y-m')]['total']) ?></strong></p>
                <?php else: ?>
                    <p class="muted">未入力です。<a href="<?= url_for('member/rx_counts.php'); ?>">当月の枚数を登録</a>しましょう。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
