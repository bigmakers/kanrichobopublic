<?php
function admin_nav() {
    return '<nav class="nav"><a href="' . url_for('admin/index.php') . '">ダッシュボード</a> | '
        . '<a href="' . url_for('admin/users_admin.php') . '">ユーザー管理</a> | '
        . '<a href="' . url_for('admin/records_admin.php') . '">記録管理</a> | '
        . '<a href="' . url_for('admin/training_admin.php') . '">研修教材管理</a> | '
        . '<a href="' . url_for('admin/backup.php') . '">バックアップ</a> | '
        . '<a href="' . url_for('index.php') . '">ユーザーページへ</a></nav>';
}
?>
