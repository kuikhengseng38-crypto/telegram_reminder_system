<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$action = (string) (query('action') ?: post('action') ?: 'list');
$payload = is_post() ? require_json_post() : $_GET;

if ($action !== 'list') {
    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

$q = trim((string) ($payload['q'] ?? ''));
$filter = (string) ($payload['filter'] ?? 'all');

$sql = "SELECT ml.id, ml.reminder_id, ml.chat_id, ml.message_text, ml.status, ml.sent_time, ml.error_message,
               r.title, u.name AS user_name
        FROM message_logs ml
        LEFT JOIN reminders r ON r.id = ml.reminder_id
        LEFT JOIN users u ON u.chat_id = ml.chat_id";
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(r.title LIKE ? OR ml.chat_id LIKE ? OR ml.message_text LIKE ? OR u.name LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

switch ($filter) {
    case 'today':
        $where[] = 'DATE(ml.sent_time) = CURDATE()';
        break;
    case 'sent':
        $where[] = "ml.status = 'sent'";
        break;
    case 'failed':
        $where[] = "ml.status = 'failed'";
        break;
    case '7days':
        $where[] = 'ml.sent_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        break;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ml.id DESC LIMIT 400';

$stmt = db()->prepare($sql);
$stmt->execute($params);
json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
