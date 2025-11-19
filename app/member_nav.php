<?php
function member_nav() {
    return '<nav class="nav"><a href="/index.php">ホーム</a> | <a href="/member/account.php">アカウント設定</a> | '
        . '<a href="/member/checklist.php">薬局管理帳簿</a> | <a href="/member/rx_counts.php">処方箋枚数</a> | '
        . '<a href="/member/my_schedule.php">マイスケジュール</a> | <a href="/member/training_materials.php">研修教材</a> | '
        . '<a href="/member/pharmacists.php">薬剤師名簿</a> | <a href="/member/print_month.php">印刷</a> | '
        . '<a href="/member/backup.php">バックアップ</a> | <a href="/guide.php">使い方</a> | <a href="/logout.php">ログアウト</a></nav>';
}
?>
