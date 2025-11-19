<?php
function member_nav() {
    return '<nav class="nav"><a href="' . url_for('index.php') . '">ホーム</a> | <a href="' . url_for('member/account.php') . '">アカウント設定</a> | '
        . '<a href="' . url_for('member/checklist.php') . '">薬局管理帳簿</a> | <a href="' . url_for('member/rx_counts.php') . '">処方箋枚数</a> | '
        . '<a href="' . url_for('member/my_schedule.php') . '">マイスケジュール</a> | <a href="' . url_for('member/training_materials.php') . '">研修教材</a> | '
        . '<a href="' . url_for('member/pharmacists.php') . '">薬剤師名簿</a> | <a href="' . url_for('member/print_month.php') . '">印刷</a> | '
        . '<a href="' . url_for('member/backup.php') . '">バックアップ</a> | <a href="' . url_for('guide.php') . '">使い方</a> | <a href="' . url_for('logout.php') . '">ログアウト</a></nav>';
}
?>
