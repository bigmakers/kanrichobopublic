<?php
function admin_nav() {
    return '<nav class="nav"><a href="/admin/index.php">ダッシュボード</a> | '
        . '<a href="/admin/users_admin.php">ユーザー管理</a> | '
        . '<a href="/admin/records_admin.php">記録管理</a> | '
        . '<a href="/admin/training_admin.php">研修教材管理</a> | '
        . '<a href="/admin/backup.php">バックアップ</a> | '
        . '<a href="/index.php">ユーザーページへ</a></nav>';
}
?>
