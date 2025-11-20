<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$schedule = load_user_meta($user['email'], 'schedule');

// POST処理（保存ロジック）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $events = [];
    $postedEvents = $_POST['events'] ?? [];
    foreach ($postedEvents as $row) {
        $d = sanitize_text($row['date'] ?? '');
        $t = sanitize_text($row['title'] ?? '');
        if ($d === '' && $t === '') { continue; }
        $events[] = [
            'date' => $d,
            'title' => $t,
            'detail' => sanitize_text($row['detail'] ?? ''),
            'participate' => !empty($row['participate'])
        ];
    }
    // 保存時は日付順（昇順）で統一して保存
    usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
    $schedule['events'] = $events;
    save_user_meta($user['email'], 'schedule', $schedule);
    header('Location: ?saved=1');
    exit;
}

// データ準備
$events = $schedule['events'] ?? [];
if (empty($events)) { $events = [['id' => uniqid()]]; }
else { foreach($events as &$e) { $e['id'] = uniqid(); } }

// 未来と過去に分割
$today = date('Y-m-d');
$futureEvents = [];
$pastEvents = [];

foreach ($events as $e) {
    if (empty($e['date'])) continue; // 日付未定はスキップ（または別途表示）
    if ($e['date'] < $today) {
        $pastEvents[] = $e;
    } else {
        $futureEvents[] = $e;
    }
}

// ソート順序の調整
// 未来: 近い順（昇順）
usort($futureEvents, fn($a, $b) => strcmp($a['date'], $b['date']));
// 過去: 新しい順（降順）→ 直近の過去が見やすいように
usort($pastEvents, fn($a, $b) => strcmp($b['date'], $a['date']));

// グループ化関数
function group_events($list) {
    $groups = [];
    foreach ($list as $e) {
        // ソートキー用に Y-m 形式を使用（1月と10月の混同防止）
        $sortKey = date('Y-m', strtotime($e['date']));
        $label = date('Y年n月', strtotime($e['date']));
        
        if (!isset($groups[$sortKey])) {
            $groups[$sortKey] = ['label' => $label, 'items' => []];
        }
        $groups[$sortKey]['items'][] = $e;
    }
    return $groups; // 入力配列の順序に基づいてキーが生成されるため、sort不要
}

$futureGroups = group_events($futureEvents);
$pastGroups = group_events($pastEvents);

$isSaved = !empty($_GET['saved']);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイスケジュール</title>
<link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<style>
    /* レイアウト */
    .layout-grid {
        display: grid; grid-template-columns: 1fr 400px; gap: 24px; align-items: start;
    }
    @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }

    /* タブナビゲーション */
    .tab-nav { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border); }
    .tab-btn {
        padding: 10px 20px; cursor: pointer; font-weight: bold; color: var(--text-muted);
        background: none; border: none; border-bottom: 3px solid transparent; margin-bottom: -2px;
        transition: 0.2s; font-size: 1rem;
    }
    .tab-btn:hover { color: var(--primary); background: #f9fafb; }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
    
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    /* タイムライン */
    .timeline-section { position: relative; padding-left: 24px; margin-bottom: 30px; }
    .timeline-section::before {
        content: ''; position: absolute; left: 6px; top: 0; bottom: 0;
        width: 2px; background: #e5e7eb;
    }
    
    .month-header {
        font-size: 1rem; font-weight: bold; color: #fff;
        background: var(--secondary); padding: 4px 12px; border-radius: 15px;
        display: inline-block; margin-bottom: 16px; position: relative; z-index: 2;
    }
    .timeline-section:first-child .month-header { background: var(--primary); } /* 最新月を強調 */

    .event-card {
        background: #fff; border: 1px solid var(--border); border-radius: 8px;
        padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: flex; gap: 16px; align-items: flex-start;
        position: relative;
    }
    .event-card::before {
        /* タイムラインのドット */
        content: ''; position: absolute; left: -23px; top: 20px;
        width: 10px; height: 10px; border-radius: 50%;
        background: #fff; border: 2px solid var(--secondary);
    }
    .event-card.today { border: 2px solid var(--primary); background: #eff6ff; }
    .event-card.today::before { border-color: var(--primary); background: var(--primary); }
    
    .date-badge {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px;
        color: var(--text-main); font-weight: bold; line-height: 1.1;
    }
    .date-badge .day { font-size: 1.3rem; }
    .date-badge .dow { font-size: 0.7rem; color: var(--text-muted); }
    .event-card.today .date-badge { background: var(--primary); color: white; }
    .event-card.today .date-badge .dow { color: #dbeafe; }

    .event-content { flex: 1; }
    .event-title { font-weight: bold; font-size: 1.1rem; margin-bottom: 4px; }
    .event-detail { color: var(--text-muted); font-size: 0.9rem; white-space: pre-wrap; line-height: 1.4; }
    .tag-participate {
        display: inline-block; font-size: 0.75rem; padding: 2px 8px;
        border-radius: 4px; background: #d1fae5; color: #065f46;
        margin-top: 6px; font-weight: bold;
    }

    /* 編集エリア */
    .edit-area { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid var(--border); position: sticky; top: 20px; }
    .edit-row { border-bottom: 1px solid #f3f4f6; padding-bottom: 16px; margin-bottom: 16px; position: relative; }
    .btn-delete {
        position: absolute; top: 0; right: 0; background: none; border: none;
        color: #9ca3af; cursor: pointer; font-size: 1.2rem; padding: 0 5px;
    }
    .btn-delete:hover { color: var(--danger); }
</style>
</head>
<body>

<div class="container">
    <h1>マイスケジュール</h1>
    <?= member_nav(); ?>
    <div id="toast" class="toast">保存しました</div>

    <div class="layout-grid">
        <div class="col-view">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('future')">今後の予定</button>
                <button class="tab-btn" onclick="switchTab('past')">過去の履歴</button>
            </div>

            <div id="tab-future" class="tab-content active">
                <?php if (empty($futureGroups)): ?>
                    <div class="card" style="text-align:center; padding:40px; color:var(--text-muted);">
                        <p>今後の予定はありません。<br>右側のフォームから追加してください。</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($futureGroups as $group): ?>
                        <div class="timeline-section">
                            <div class="month-header"><?= htmlspecialchars($group['label']) ?></div>
                            <?php foreach ($group['items'] as $ev): 
                                $isToday = $ev['date'] === $today;
                                $w = ['日','月','火','水','木','金','土'][date('w', strtotime($ev['date']))];
                                $d = date('j', strtotime($ev['date']));
                            ?>
                                <div class="event-card <?= $isToday ? 'today' : '' ?>">
                                    <div class="date-badge">
                                        <span class="day"><?= $d ?></span>
                                        <span class="dow"><?= $w ?></span>
                                    </div>
                                    <div class="event-content">
                                        <div class="event-title">
                                            <?= htmlspecialchars($ev['title']) ?>
                                            <?php if($isToday): ?><span style="font-size:0.8rem; color:var(--primary); margin-left:8px;">● 今日</span><?php endif; ?>
                                        </div>
                                        <?php if (!empty($ev['detail'])): ?>
                                            <div class="event-detail"><?= nl2br(htmlspecialchars($ev['detail'])) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($ev['participate'])): ?>
                                            <div class="tag-participate">参加記録対象</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="tab-past" class="tab-content">
                <?php if (empty($pastGroups)): ?>
                    <div class="card" style="padding:20px; color:var(--text-muted);">過去の履歴はありません。</div>
                <?php else: ?>
                    <?php foreach ($pastGroups as $group): ?>
                        <div class="timeline-section">
                            <div class="month-header" style="background:#9ca3af;"><?= htmlspecialchars($group['label']) ?></div>
                            <?php foreach ($group['items'] as $ev): 
                                $w = ['日','月','火','水','木','金','土'][date('w', strtotime($ev['date']))];
                                $d = date('j', strtotime($ev['date']));
                            ?>
                                <div class="event-card" style="opacity:0.8; background:#f9fafb;">
                                    <div class="date-badge" style="background:#e5e7eb;">
                                        <span class="day"><?= $d ?></span>
                                        <span class="dow"><?= $w ?></span>
                                    </div>
                                    <div class="event-content">
                                        <div class="event-title"><?= htmlspecialchars($ev['title']) ?></div>
                                        <?php if (!empty($ev['detail'])): ?>
                                            <div class="event-detail"><?= nl2br(htmlspecialchars($ev['detail'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" class="col-edit">
            <?= csrf_field(); ?>
            <div class="edit-area">
                <div class="card-title">予定の編集・追加</div>
                <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
                    入力して保存すると、自動的に日付順に並び替わります。
                </div>

                <div id="events-container">
                    <?php foreach ($events as $e): 
                        $uid = $e['id'] ?? uniqid(); 
                    ?>
                        <div class="edit-row" id="row-<?= $uid ?>">
                            <button type="button" class="btn-delete" onclick="removeRow('<?= $uid ?>')" title="削除">×</button>
                            <div style="margin-bottom:8px;">
                                <label style="font-size:0.85rem;">日付</label>
                                <input type="date" name="events[<?= $uid ?>][date]" value="<?= htmlspecialchars($e['date'] ?? '') ?>">
                            </div>
                            <div style="margin-bottom:8px;">
                                <label style="font-size:0.85rem;">タイトル</label>
                                <input type="text" name="events[<?= $uid ?>][title]" value="<?= htmlspecialchars($e['title'] ?? '') ?>" placeholder="タイトル">
                            </div>
                            <div style="margin-bottom:8px;">
                                <textarea name="events[<?= $uid ?>][detail]" rows="1" placeholder="メモ（任意）" style="font-size:0.9rem;"><?= htmlspecialchars($e['detail'] ?? '') ?></textarea>
                            </div>
                            <label style="display:flex; align-items:center; font-size:0.85rem;">
                                <input type="checkbox" name="events[<?= $uid ?>][participate]" value="1" <?= !empty($e['participate'])?'checked':''; ?>>
                                <span style="margin-left:4px;">参加記録をつける</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="btn w-100 secondary" id="btn-add-row" style="margin-bottom:12px; border:1px dashed #aaa; background:#f9fafb; color:#555;">+ 行を追加</button>
                <button type="submit" class="btn w-100">保存する</button>
            </div>
        </form>
    </div>
</div>

<script>
// トースト表示
<?php if($isSaved): ?>
    const t = document.getElementById('toast');
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
    history.replaceState(null, null, location.pathname);
<?php endif; ?>

// タブ切り替え
function switchTab(target) {
    // ボタンの状態
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    // コンテンツの表示
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + target).classList.add('active');
}

// 行追加
document.getElementById('btn-add-row').addEventListener('click', function() {
    const container = document.getElementById('events-container');
    const uid = 'new_' + Date.now();
    
    const div = document.createElement('div');
    div.className = 'edit-row';
    div.id = 'row-' + uid;
    div.innerHTML = `
        <button type="button" class="btn-delete" onclick="removeRow('${uid}')">×</button>
        <div style="margin-bottom:8px;">
            <label style="font-size:0.85rem;">日付</label>
            <input type="date" name="events[${uid}][date]">
        </div>
        <div style="margin-bottom:8px;">
            <label style="font-size:0.85rem;">タイトル</label>
            <input type="text" name="events[${uid}][title]" placeholder="新しい予定">
        </div>
        <div style="margin-bottom:8px;">
            <textarea name="events[${uid}][detail]" rows="1" placeholder="メモ（任意）" style="font-size:0.9rem;"></textarea>
        </div>
        <label style="display:flex; align-items:center; font-size:0.85rem;">
            <input type="checkbox" name="events[${uid}][participate]" value="1">
            <span style="margin-left:4px;">参加記録をつける</span>
        </label>
    `;
    container.appendChild(div);
});

// 行削除
window.removeRow = function(uid) {
    const row = document.getElementById('row-' + uid);
    if (row && confirm('この予定を削除しますか？')) {
        row.remove();
    }
};
</script>
</body>
</html>
