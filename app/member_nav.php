<?php
function member_nav() {
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
    $isActive = function ($path) use ($currentUrl) {
        return strpos($currentUrl, $path) !== false ? 'active' : '';
    };

    ob_start();
    ?>
    <nav class="app-navbar">
        <div class="nav-start">
            <a href="<?= htmlspecialchars(url_for('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-brand" title="ホーム">🏠</a>
            <a href="<?= htmlspecialchars(url_for('member/checklist.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('checklist.php') ?>">日報・帳簿</a>
            <a href="<?= htmlspecialchars(url_for('member/my_schedule.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('my_schedule.php') ?>">スケジュール</a>
            <a href="<?= htmlspecialchars(url_for('member/history.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('history.php') ?>">過去ログ</a>
        </div>
        <div class="nav-end">
            <div class="nav-dropdown">
                <button type="button" class="nav-item dropdown-toggle">管理・ツール ▾</button>
                <div class="dropdown-menu">
                    <a href="<?= htmlspecialchars(url_for('member/rx_counts.php'), ENT_QUOTES, 'UTF-8') ?>">処方箋集計</a>
                    <a href="<?= htmlspecialchars(url_for('member/pharmacists.php'), ENT_QUOTES, 'UTF-8') ?>">薬剤師名簿</a>
                    <a href="<?= htmlspecialchars(url_for('member/training_materials.php'), ENT_QUOTES, 'UTF-8') ?>">研修教材</a>
                    <div class="divider"></div>
                    <a href="<?= htmlspecialchars(url_for('member/print_month.php'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">印刷</a>
                    <a href="<?= htmlspecialchars(url_for('member/backup.php'), ENT_QUOTES, 'UTF-8') ?>">バックアップ</a>
                </div>
            </div>
            <a href="<?= htmlspecialchars(url_for('guide.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item icon-only" title="使い方">?</a>
            <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                <a href="<?= htmlspecialchars(url_for('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item nav-admin-link">管理画面</a>
            <?php endif; ?>
            <div class="nav-dropdown">
                <button type="button" class="nav-item dropdown-toggle">アカウント ▾</button>
                <div class="dropdown-menu right-aligned">
                    <a href="<?= htmlspecialchars(url_for('member/account.php'), ENT_QUOTES, 'UTF-8') ?>">設定変更</a>
                    <a href="<?= htmlspecialchars(url_for('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-danger">ログアウト</a>
                </div>
            </div>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}
?>
