<?php
function member_nav() {
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    $isActive = fn($path) => strpos($current_url, $path) !== false ? 'active' : '';
    ?>
    <nav class="app-navbar">
        <div class="nav-start">
            <a href="<?= htmlspecialchars(url_for('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-brand">🏠</a>

            <a href="<?= htmlspecialchars(url_for('member/checklist.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('checklist.php') ?>">
                日報・帳簿
            </a>
            <a href="<?= htmlspecialchars(url_for('member/my_schedule.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('my_schedule.php') ?>">
                スケジュール
            </a>
            <a href="<?= htmlspecialchars(url_for('member/training_materials.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('training_materials.php') ?>">
                研修教材
            </a>
            <a href="<?= htmlspecialchars(url_for('member/history.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $isActive('history.php') ?>">
                過去ログ
            </a>
        </div>

        <div class="nav-end">
            <div class="nav-dropdown">
                <button class="nav-item dropdown-toggle">管理・ツール ▾</button>
                <div class="dropdown-menu">
                    <a href="<?= htmlspecialchars(url_for('member/rx_counts.php'), ENT_QUOTES, 'UTF-8') ?>">処方箋集計</a>
                    <a href="<?= htmlspecialchars(url_for('member/pharmacists.php'), ENT_QUOTES, 'UTF-8') ?>">薬剤師名簿</a>
                    <div class="divider"></div>
                    <a href="<?= htmlspecialchars(url_for('member/print_month.php'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">印刷</a>
                    <a href="<?= htmlspecialchars(url_for('member/backup.php'), ENT_QUOTES, 'UTF-8') ?>">バックアップ</a>
                </div>
            </div>

            <a href="<?= htmlspecialchars(url_for('guide.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item icon-only" title="使い方">
                <span style="font-weight:bold; border:1px solid currentColor; border-radius:50%; width:20px; height:20px; display:inline-flex; justify-content:center; align-items:center;">?</span>
            </a>

            <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                <a href="<?= htmlspecialchars(url_for('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item nav-admin-link">管理画面</a>
            <?php endif; ?>

            <div class="nav-dropdown">
                <button class="nav-item dropdown-toggle">アカウント ▾</button>
                <div class="dropdown-menu right-aligned">
                    <a href="<?= htmlspecialchars(url_for('member/account.php'), ENT_QUOTES, 'UTF-8') ?>">設定変更</a>
                    <a href="<?= htmlspecialchars(url_for('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-danger">ログアウト</a>
                </div>
            </div>
        </div>
    </nav>
    <?php
}
?>
