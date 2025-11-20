<?php
require_once __DIR__ . '/../../app/bootstrap.php';
enforce_login();
$user = $_SESSION['user'];
csrf_check();
$schedule = load_user_meta($user['email'], 'schedule');
if (!is_array($schedule)) { $schedule = []; }

// 保存・削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $events = $schedule['events'] ?? [];
    if (!is_array($events)) { $events = []; }
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? uniqid();
        $date = sanitize_text($_POST['date'] ?? '');
        $title = sanitize_text($_POST['title'] ?? '');
        $detail = sanitize_text($_POST['detail'] ?? '');

        // 既存のIDがあれば更新、なければ追加
        $found = false;
        foreach ($events as &$ev) {
            if (($ev['id'] ?? '') === $id) {
                $ev['date'] = $date;
                $ev['title'] = $title;
                $ev['detail'] = $detail;
                $found = true;
                break;
            }
        }
        unset($ev);
        if (!$found) {
            $events[] = [
                'id' => $id,
                'date' => $date,
                'title' => $title,
                'detail' => $detail
            ];
        }
    } elseif ($action === 'delete') {
        $targetId = $_POST['id'] ?? '';
        $events = array_values(array_filter($events, fn($e) => ($e['id'] ?? '') !== $targetId));
    }

    // 日付順ソートして保存
    usort($events, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
    $schedule['events'] = $events;
    save_user_meta($user['email'], 'schedule', $schedule);
    
    // リダイレクト（二重送信防止）
    header('Location: ?ym=' . urlencode(sanitize_text($_POST['ym'] ?? date('Y-m'))));
    exit;
}

// カレンダー表示ロジック
$ym = sanitize_text($_GET['ym'] ?? date('Y-m'));
$timestamp = strtotime($ym . '-01');
if ($timestamp === false) { $timestamp = time(); }
$currentYm = date('Y-m', $timestamp);
$prevYm = date('Y-m', strtotime('-1 month', $timestamp));
$nextYm = date('Y-m', strtotime('+1 month', $timestamp));
$titleYm = date('Y年n月', $timestamp);

// カレンダー作成
$weeks = [];
$week = '';
$day_count = (int)date('t', $timestamp);
$youbi = (int)date('w', $timestamp); // 1日の曜日(0:日 - 6:土)

// 空のセル埋め（月初）
$week .= str_repeat('<td></td>', $youbi);

$allEvents = $schedule['events'] ?? [];
if (!is_array($allEvents)) { $allEvents = []; }

for ($day = 1; $day <= $day_count; $day++, $youbi++) {
    $dateStr = $currentYm . '-' . sprintf('%02d', $day);
    $isToday = ($dateStr === date('Y-m-d'));
    
    // その日のイベント抽出
    $dayEvents = array_filter($allEvents, fn($e) => ($e['date'] ?? '') === $dateStr);
    
    // セルの中身作成
    $cellContent = "<div class='day-number'>" . $day . "</div>";
    foreach ($dayEvents as $ev) {
        $cellContent .= "<div class='cal-event' onclick='editEvent(" . json_encode($ev) . ", event)'>" . htmlspecialchars($ev['title'] ?? '') . "</div>";
    }
    
    // セルをクリックしたら新規登録モーダルへ
    $week .= "<td class='" . ($isToday ? 'today' : '') . "' onclick='openModal(\"$dateStr\")'>$cellContent</td>";
    
    if ($youbi % 7 == 6 || $day == $day_count) {
        if ($day == $day_count) {
            $week .= str_repeat('<td></td>', 6 - ($youbi % 7));
        }
        $weeks[] = '<tr>' . $week . '</tr>';
        $week = '';
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイスケジュール</title>
<link rel="stylesheet" href="<?= htmlspecialchars(url_for('styles.css'), ENT_QUOTES) ?>">
<style>
    /* カレンダー専用スタイル */
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .calendar-nav { font-size: 1.5rem; font-weight: bold; text-decoration: none; color: var(--primary); user-select: none; }
    .calendar-title { font-size: 1.5rem; font-weight: bold; }
    
    .calendar-table { width: 100%; border-collapse: collapse; table-layout: fixed; background: #fff; box-shadow: var(--shadow); border-radius: var(--radius); overflow: hidden; }
    .calendar-table th { background: #f9fafb; padding: 10px; border-bottom: 1px solid var(--border); color: var(--text-muted); }
    .calendar-table td { border: 1px solid var(--border); padding: 5px; vertical-align: top; height: 100px; cursor: pointer; transition: background 0.2s; }
    .calendar-table td:hover { background: #f3f4f6; }
    .calendar-table td.today { background: #eff6ff; border: 2px solid var(--primary); }
    
    .day-number { font-weight: bold; margin-bottom: 4px; font-size: 0.9rem; color: var(--text-muted); }
    .today .day-number { color: var(--primary); }
    
    .cal-event {
        background: var(--primary); color: white; font-size: 0.75rem; padding: 2px 4px;
        border-radius: 4px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cal-event:hover { opacity: 0.8; }

    /* モーダル */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
    }
    .modal-card {
        background: #fff; padding: 24px; border-radius: 8px; width: 90%; max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-actions { display: flex; justify-content: space-between; margin-top: 20px; gap: 10px; }
    .btn-delete { background: var(--danger); color: #fff; border: none; padding: 10px 14px; border-radius: 8px; cursor: pointer; }
    .btn-delete:hover { background: #dc2626; }
</style>
</head>
<body class="mono">
<div class="layout">
    <h1>マイスケジュール</h1>
    <?= member_nav(); ?>

    <div class="calendar-header">
        <a href="?ym=<?= htmlspecialchars($prevYm, ENT_QUOTES) ?>" class="calendar-nav">‹</a>
        <span class="calendar-title"><?= htmlspecialchars($titleYm) ?></span>
        <a href="?ym=<?= htmlspecialchars($nextYm, ENT_QUOTES) ?>" class="calendar-nav">›</a>
    </div>

    <table class="calendar-table">
        <thead><tr>
            <th style="color:#ef4444;">日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th style="color:#3b82f6;">土</th>
        </tr></thead>
        <tbody>
            <?= implode('', $weeks) ?>
        </tbody>
    </table>

    <div style="margin-top:16px; color:var(--text-muted); font-size:0.9rem;">
        ※日付をタップすると予定を追加できます。予定をタップすると編集・削除できます。<br>
        ※ここに登録した予定は、すべて自動的に日報（チェックリスト）に表示されます。
    </div>
</div>

<div id="eventModal" class="modal-overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal-card">
        <h3 id="modalTitle">予定の編集</h3>
        <form method="post">
            <?= csrf_field(); ?>
            <input type="hidden" name="ym" value="<?= htmlspecialchars($currentYm, ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="evtId">
            
            <label>日付</label>
            <input type="date" name="date" id="evtDate" required>
            
            <label>タイトル</label>
            <input type="text" name="title" id="evtTitle" placeholder="例：メーカー勉強会" required>
            
            <label>詳細メモ</label>
            <textarea name="detail" id="evtDetail" rows="3" placeholder="場所や持ち物など"></textarea>
            
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeModal()">キャンセル</button>
                <div>
                    <button type="submit" class="btn-delete" name="action" value="delete" id="btnDelete" onclick="return confirm('削除しますか？')" style="margin-right:10px;">削除</button>
                    <button type="submit" class="btn">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(dateStr) {
    document.getElementById('eventModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = '予定の追加';
    document.getElementById('evtId').value = 'new_' + Date.now();
    document.getElementById('evtDate').value = dateStr;
    document.getElementById('evtTitle').value = '';
    document.getElementById('evtDetail').value = '';
    document.getElementById('btnDelete').style.display = 'none'; // 新規時は削除ボタン隠す
}

function editEvent(ev, e) {
    e.stopPropagation(); // 親セルのクリックイベントを止める
    document.getElementById('eventModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = '予定の編集';
    document.getElementById('evtId').value = ev.id || '';
    document.getElementById('evtDate').value = ev.date;
    document.getElementById('evtTitle').value = ev.title;
    document.getElementById('evtDetail').value = ev.detail || '';
    document.getElementById('btnDelete').style.display = 'inline-block';
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
}
</script>
</body>
</html>
