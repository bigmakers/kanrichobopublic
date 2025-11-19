<?php
require_once __DIR__ . '/../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];

// 本日の日付
$today = date('Y-m-d');
$checklist = load_user_meta($user['email'], 'checklists');
$schedule = load_user_meta($user['email'], 'schedule');

// 本日のチェック状況確認
$todayEntry = $checklist[$today] ?? null;
$isCheckedIn = !empty($todayEntry) && !empty($todayEntry['checks']); // 何かチェックしていれば着手済みとみなす

// 本日の予定確認
$todayEvents = [];
foreach (($schedule['events'] ?? []) as $ev) {
    if (($ev['date'] ?? '') === $today) {
        $todayEvents[] = $ev;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ダッシュボード - 薬局管理帳簿</title>
<link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    .stat-card {
        background: #fff;
        padding: 24px;
        border-radius: 8px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        height: 100%;
        box-sizing: border-box;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); border-color: var(--primary); }
    .stat-value { font-size: 2rem; font-weight: bold; color: var(--text-main); margin: 10px 0; }
    .stat-label { color: var(--text-muted); font-size: 0.9rem; font-weight: bold; }
    .status-badge {
        display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold;
    }
    .status-done { background: #dcfce7; color: #166534; }
    .status-todo { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>ホーム</h1>
        <div style="text-align:right;">
            <div style="font-weight:bold;"><?= htmlspecialchars($user['name'] ?? 'ゲスト') ?> さん</div>
            <div style="font-size:0.9rem; color:var(--text-muted);"><?= date('Y年m月d日') ?></div>
        </div>
    </div>

    <?= member_nav(); ?>

    <div class="dashboard-grid">
        <a href="<?= url_for('member/checklist.php') ?>" class="stat-card" style="text-decoration:none;">
            <div class="stat-label">本日の業務チェック</div>
            <div style="margin: 15px 0;">
                <?php if ($isCheckedIn): ?>
                    <span class="status-badge status-done">記録あり</span>
                    <span style="font-size:0.9rem; color:var(--text-muted); margin-left:8px;">
                        項目数: <?= count($todayEntry['checks']) ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge status-todo">未着手</span>
                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:8px;">
                        業務を開始したらここをクリック
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top:auto; color:var(--primary); font-weight:bold;">
                日報を開く →
            </div>
        </a>

        <a href="<?= url_for('member/my_schedule.php') ?>" class="stat-card" style="text-decoration:none;">
            <div class="stat-label">本日の予定</div>
            <div class="stat-value" style="font-size:1.5rem;">
                <?= count($todayEvents) ?> <span style="font-size:1rem; font-weight:normal;">件</span>
            </div>
            <div style="color:var(--text-muted); font-size:0.9rem; margin-bottom:15px;">
                <?php if ($todayEvents): ?>
                    直近: <?= htmlspecialchars($todayEvents[0]['title']) ?>
                <?php else: ?>
                    予定はありません
                <?php endif; ?>
            </div>
            <div style="margin-top:auto; color:var(--primary); font-weight:bold;">
                スケジュールを確認 →
            </div>
        </a>

        <div class="stat-card" style="justify-content:center; align-items:center; background:#f9fafb; border-style:dashed;">
            <div style="color:var(--text-muted); font-weight:bold;">
                集計・設定
            </div>
            <div style="margin-top:10px; display:flex; gap:10px;">
                <a href="<?= url_for('member/history.php') ?>" class="btn secondary" style="font-size:0.85rem;">過去ログ</a>
                <a href="<?= url_for('member/account.php') ?>" class="btn secondary" style="font-size:0.85rem;">設定</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
