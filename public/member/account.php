<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$account = load_user_meta($user['email'], 'account');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete_account') {
        delete_user_records($user['email']);
        logout();
        header('Location: ' . url_for('login.php') . '?msg=' . urlencode('退会処理が完了しました。ご利用ありがとうございました。'));
        exit;
    }
    $fields = [
        'permit_expiry','insurance_code','pmda','manager_name','founder_name','permit_number','permit_date','license_expiry',
        'narcotics_number','narcotics_expiry','insurance_pharmacy_code','institution_code','pmda_certificate','pmda_email','pharmacy_name'
    ];
    foreach ($fields as $f) {
        $account[$f] = sanitize_text($_POST[$f] ?? ($account[$f] ?? ''));
    }
    $account['facilities'] = array_filter(array_map('sanitize_text', explode(',', $_POST['facilities'] ?? '')));
    $account['public_frames'] = array_filter(array_map('sanitize_text', explode(',', $_POST['public_frames'] ?? '')));
    $checkItems = [];
    foreach (($_POST['check_items'] ?? []) as $item) {
        $clean = sanitize_text($item);
        if ($clean !== '') {
            $checkItems[] = $clean;
        }
    }
    $account['check_items'] = array_slice($checkItems, 0, 30);

    $newEmail = strtolower(sanitize_text($_POST['email'] ?? $user['email']));
    $newName = sanitize_text($_POST['name'] ?? $user['name']);
    $newPharmacy = sanitize_text($_POST['pharmacy'] ?? ($account['pharmacy_name'] ?? $user['pharmacy'] ?? ''));
    $users = users_all();
    $exists = auth_user_by_email($newEmail);
    if ($exists && $exists['email'] !== $user['email']) {
        $message = 'このメールアドレスは既に使用されています';
    } else {
        if (!empty($_POST['password'])) {
            $user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $user['name'] = $newName;
        $user['pharmacy'] = $newPharmacy;
        if ($newEmail !== $user['email']) {
            migrate_user_email($user['email'], $newEmail);
            $user['email'] = $newEmail;
        }
        save_user($user);
        save_user_meta($user['email'], 'account', $account);
        $_SESSION['user'] = $user;
        $message = '保存しました';
    }
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="UTF-8"><title>アカウント設定</title><link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>"></head>
<body class="mono">
<h1>アカウント設定</h1>
<?= member_nav(); ?>
<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <div class="grid-2">
        <label>氏名<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></label>
        <label>メールアドレス<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label>
        <label>所属薬局<input type="text" name="pharmacy" value="<?= htmlspecialchars($account['pharmacy_name'] ?? $user['pharmacy'] ?? '') ?>" placeholder="薬局名を入力"></label>
        <label>パスワード変更<input type="password" name="password" placeholder="変更する場合のみ入力"></label>
        <label>管理薬剤師名<input type="text" name="manager_name" value="<?= htmlspecialchars($account['manager_name'] ?? '') ?>"></label>
        <label>開設者氏名<input type="text" name="founder_name" value="<?= htmlspecialchars($account['founder_name'] ?? '') ?>"></label>
        <label>薬局開設許可番号<input type="text" name="permit_number" value="<?= htmlspecialchars($account['permit_number'] ?? '') ?>"></label>
        <label>許可年月日<input type="date" name="permit_date" value="<?= htmlspecialchars($account['permit_date'] ?? '') ?>"></label>
        <label>免許有効期限<input type="date" name="license_expiry" value="<?= htmlspecialchars($account['license_expiry'] ?? '') ?>"></label>
        <label>許可期限<input type="date" name="permit_expiry" value="<?= htmlspecialchars($account['permit_expiry'] ?? '') ?>"></label>
        <label>麻薬許可番号<input type="text" name="narcotics_number" value="<?= htmlspecialchars($account['narcotics_number'] ?? '') ?>"></label>
        <label>麻薬許可番号有効期限<input type="date" name="narcotics_expiry" value="<?= htmlspecialchars($account['narcotics_expiry'] ?? '') ?>"></label>
        <label>保険薬局コード<input type="text" name="insurance_pharmacy_code" value="<?= htmlspecialchars($account['insurance_pharmacy_code'] ?? '') ?>"></label>
        <label>保険機関コード<input type="text" name="institution_code" value="<?= htmlspecialchars($account['institution_code'] ?? '') ?>"></label>
        <label>保険薬局/機関コード<input type="text" name="insurance_code" value="<?= htmlspecialchars($account['insurance_code'] ?? '') ?>" placeholder="10桁コードなど"></label>
        <label>PMDAメディナビ登録証明書番号<input type="text" name="pmda_certificate" value="<?= htmlspecialchars($account['pmda_certificate'] ?? '') ?>"></label>
        <label>PMDAメディナビ登録メールアドレス<input type="email" name="pmda_email" value="<?= htmlspecialchars($account['pmda_email'] ?? '') ?>"></label>
        <label>PMDAメディナビ情報<input type="text" name="pmda" value="<?= htmlspecialchars($account['pmda'] ?? '') ?>" placeholder="URLやID"></label>
    </div>
    <label>施設基準（カンマ区切り）<input type="text" name="facilities" value="<?= htmlspecialchars(implode(',', $account['facilities'] ?? [])) ?>" placeholder="例: 基準1,基準2"></label>
    <label>公費枠（カンマ区切り）<input type="text" name="public_frames" value="<?= htmlspecialchars(implode(',', $account['public_frames'] ?? [])) ?>" placeholder="例: 公費1,公費2"></label>
    <p class="muted">複数ある場合はカンマ「,」で区切ってください。</p>
    <div class="card" style="background:#fafafa;">
        <h3>チェック項目（30まで）</h3>
        <p class="muted">日替わりで3つ抽出され、薬局管理帳簿にチェックボックスとして表示されます。</p>
        <div class="grid-3">
            <?php for($i=0;$i<30;$i++): $val=$account['check_items'][$i] ?? ''; ?>
                <label>#<?= $i+1 ?> <input type="text" name="check_items[]" value="<?= htmlspecialchars($val) ?>" placeholder="チェック項目"></label>
            <?php endfor; ?>
        </div>
    </div>
    <button type="submit">保存</button>
</form>

<div class="card danger">
    <h3>退会</h3>
    <p>退会すると個人の記録は削除されます。必要に応じて事前に<a href="<?= url_for('member/backup.php'); ?>">バックアップ</a>を取得してください。</p>
    <form method="post" onsubmit="return confirm('バックアップは取得しましたか？退会するとデータが削除されます。よろしいですか？');">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="delete_account">
        <button type="submit">退会する</button>
    </form>
</div>
</body></html>
