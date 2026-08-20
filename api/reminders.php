<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$action = (string) (query('action') ?: post('action'));
$payload = is_post() ? require_json_post() : $_GET;
if ($action === '') {
    $action = (string) ($payload['action'] ?? 'list');
}

switch ($action) {
    case 'list':
        json_response(['ok' => true, 'data' => list_reminders($payload)]);

    case 'get':
        $id = (int) ($payload['id'] ?? 0);
        json_response(['ok' => true, 'data' => get_reminder($id)]);

    case 'save':
        json_response(save_reminder($payload));

    case 'delete':
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1) {
            json_response(['ok' => false, 'error' => 'Invalid reminder.']);
        }
        $del = db()->prepare('DELETE FROM reminders WHERE id = ?');
        $del->execute([$id]);
        json_response(['ok' => true]);

    case 'send_now':
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1) {
            json_response(['ok' => false, 'error' => 'Invalid reminder.']);
        }
        $stmt = db()->prepare('SELECT status FROM reminders WHERE id = ?');
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        if ($status !== 'pending') {
            json_response(['ok' => false, 'error' => 'Only pending reminders can be sent now.']);
        }
        @set_time_limit(120);
        $ok = process_one_reminder($id);
        $fresh = db()->prepare('SELECT status FROM reminders WHERE id = ?');
        $fresh->execute([$id]);
        json_response([
            'ok' => $ok,
            'status' => (string) $fresh->fetchColumn(),
            'error' => $ok ? null : 'Send did not start. Please refresh and try again.',
        ]);

    case 'dispatch_due':
        @set_time_limit(120);
        $count = process_due_reminders();
        json_response(['ok' => true, 'processed' => $count]);

    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

function list_reminders(array $payload): array
{
    $q = trim((string) ($payload['q'] ?? ''));
    $filter = (string) ($payload['filter'] ?? 'all');

    $sql = "SELECT r.id, r.title, r.scheduled_time, r.status, r.created_at,
                   (SELECT COUNT(*) FROM reminder_messages rm WHERE rm.reminder_id = r.id) AS message_count,
                   (SELECT COUNT(*) FROM reminder_recipients rr WHERE rr.reminder_id = r.id) AS recipient_count
            FROM reminders r";
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(r.title LIKE ?
            OR EXISTS (SELECT 1 FROM reminder_recipients rr WHERE rr.reminder_id = r.id AND rr.chat_id LIKE ?)
            OR EXISTS (SELECT 1 FROM reminder_messages rm WHERE rm.reminder_id = r.id AND rm.message_text LIKE ?))';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    switch ($filter) {
        case 'today':
            $where[] = 'DATE(r.scheduled_time) = CURDATE()';
            break;
        case 'pending':
            $where[] = "r.status = 'pending'";
            break;
        case 'sent':
            $where[] = "r.status = 'sent'";
            break;
        case 'failed':
            $where[] = "r.status IN ('failed','partial')";
            break;
        case '7days':
            $where[] = 'r.scheduled_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY r.scheduled_time DESC, r.id DESC LIMIT 300';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_reminder(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM reminders WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $ms = db()->prepare('SELECT id, message_text, sort_order FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order, id');
    $ms->execute([$id]);
    $rr = db()->prepare('SELECT chat_id FROM reminder_recipients WHERE reminder_id = ?');
    $rr->execute([$id]);
    $row['messages'] = $ms->fetchAll();
    $row['chat_ids'] = array_column($rr->fetchAll(), 'chat_id');
    return $row;
}

function save_reminder(array $payload): array
{
    $id = (int) ($payload['id'] ?? 0);
    $title = trim((string) ($payload['title'] ?? ''));
    $scheduled = trim((string) ($payload['scheduled_time'] ?? ''));
    $messages = $payload['messages'] ?? [];
    $chatIds = $payload['chat_ids'] ?? [];

    if ($title === '' || mb_strlen($title) > 255) {
        return ['ok' => false, 'error' => 'Please enter a valid title.'];
    }

    $scheduled = str_replace('T', ' ', $scheduled);
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $scheduled)) {
        return ['ok' => false, 'error' => 'Invalid schedule date/time.'];
    }
    if (strlen($scheduled) === 16) {
        $scheduled .= ':00';
    }

    if (!is_array($messages) || $messages === []) {
        return ['ok' => false, 'error' => 'Add at least one message.'];
    }
    $cleanMessages = [];
    foreach ($messages as $i => $msg) {
        $text = is_array($msg) ? trim((string) ($msg['text'] ?? $msg['message_text'] ?? '')) : trim((string) $msg);
        if ($text === '') {
            continue;
        }
        if (mb_strlen($text) > 4096) {
            return ['ok' => false, 'error' => 'Each Telegram message must be 4096 characters or fewer.'];
        }
        $cleanMessages[] = $text;
    }
    if ($cleanMessages === []) {
        return ['ok' => false, 'error' => 'Add at least one non-empty message.'];
    }

    if (!is_array($chatIds) || $chatIds === []) {
        return ['ok' => false, 'error' => 'Select at least one recipient.'];
    }
    $cleanChats = [];
    foreach ($chatIds as $chatId) {
        $chatId = trim((string) $chatId);
        if (!validate_chat_id($chatId)) {
            return ['ok' => false, 'error' => 'Invalid chat_id: ' . $chatId];
        }
        $cleanChats[$chatId] = $chatId;
    }
    $cleanChats = array_values($cleanChats);

    $pdo = db();
    if ($id > 0) {
        $chk = $pdo->prepare('SELECT status FROM reminders WHERE id = ?');
        $chk->execute([$id]);
        $status = $chk->fetchColumn();
        if (!$status) {
            return ['ok' => false, 'error' => 'Reminder not found.'];
        }
        if ($status !== 'pending') {
            return ['ok' => false, 'error' => 'Only pending reminders can be edited.'];
        }
    }

    try {
        $pdo->beginTransaction();
        if ($id > 0) {
            $upd = $pdo->prepare('UPDATE reminders SET title = ?, scheduled_time = ? WHERE id = ? AND status = ?');
            $upd->execute([$title, $scheduled, $id, 'pending']);
            $pdo->prepare('DELETE FROM reminder_messages WHERE reminder_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM reminder_recipients WHERE reminder_id = ?')->execute([$id]);
        } else {
            $ins = $pdo->prepare('INSERT INTO reminders (title, scheduled_time, status) VALUES (?, ?, ?)');
            $ins->execute([$title, $scheduled, 'pending']);
            $id = (int) $pdo->lastInsertId();
        }

        $insMsg = $pdo->prepare('INSERT INTO reminder_messages (reminder_id, message_text, sort_order) VALUES (?, ?, ?)');
        foreach ($cleanMessages as $i => $text) {
            $insMsg->execute([$id, $text, $i + 1]);
        }

        $insR = $pdo->prepare('INSERT INTO reminder_recipients (reminder_id, chat_id) VALUES (?, ?)');
        foreach ($cleanChats as $chatId) {
            $insR->execute([$id, $chatId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        write_log('app', 'save_reminder failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save reminder.'];
    }

    return ['ok' => true, 'id' => $id];
}
