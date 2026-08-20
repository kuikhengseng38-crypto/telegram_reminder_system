<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$action = (string) (query('action') ?: post('action'));
if ($action === '' && is_post()) {
    $payload = require_json_post();
    $action = (string) ($payload['action'] ?? '');
} elseif (is_post()) {
    csrf_verify_request();
    $payload = $_POST;
} else {
    $payload = $_GET;
}

switch ($action) {
    case 'list':
        $q = trim((string) ($payload['q'] ?? ''));
        $sql = 'SELECT id, name, chat_id, created_at FROM users';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE name LIKE ? OR chat_id LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);

    case 'save':
        if (!is_post()) {
            json_response(['ok' => false, 'error' => 'Invalid method'], 405);
        }
        $id = (int) ($payload['id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $chatId = trim((string) ($payload['chat_id'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) {
            json_response(['ok' => false, 'error' => 'Please enter a valid name.']);
        }
        if (!validate_chat_id($chatId)) {
            json_response(['ok' => false, 'error' => 'chat_id must be a numeric Telegram ID.']);
        }

        $dup = db()->prepare('SELECT id FROM users WHERE chat_id = ? AND id <> ? LIMIT 1');
        $dup->execute([$chatId, $id]);
        if ($dup->fetch()) {
            json_response(['ok' => false, 'error' => 'This chat_id is already registered.']);
        }

        if ($id > 0) {
            $old = db()->prepare('SELECT chat_id FROM users WHERE id = ?');
            $old->execute([$id]);
            $oldChat = $old->fetchColumn();
            if (!$oldChat) {
                json_response(['ok' => false, 'error' => 'User not found.'], 404);
            }
            $upd = db()->prepare('UPDATE users SET name = ?, chat_id = ? WHERE id = ?');
            $upd->execute([$name, $chatId, $id]);
            if ($oldChat !== $chatId) {
                $fix = db()->prepare('UPDATE reminder_recipients SET chat_id = ? WHERE chat_id = ?');
                $fix->execute([$chatId, $oldChat]);
            }
        } else {
            $ins = db()->prepare('INSERT INTO users (name, chat_id) VALUES (?, ?)');
            $ins->execute([$name, $chatId]);
            $id = (int) db()->lastInsertId();
        }
        json_response(['ok' => true, 'id' => $id]);

    case 'delete':
        if (!is_post()) {
            json_response(['ok' => false, 'error' => 'Invalid method'], 405);
        }
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1) {
            json_response(['ok' => false, 'error' => 'Invalid user.']);
        }
        $del = db()->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$id]);
        json_response(['ok' => true]);

    case 'test_send':
        if (!is_post()) {
            json_response(['ok' => false, 'error' => 'Invalid method'], 405);
        }
        $id = (int) ($payload['id'] ?? 0);
        $stmt = db()->prepare('SELECT name, chat_id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            json_response(['ok' => false, 'error' => 'User not found.'], 404);
        }
        $text = 'Hello ' . $user['name'] . '! This is a test message from ' . app_config('app_short') . '.';
        $result = sendTelegramMessage($user['chat_id'], $text);
        json_response([
            'ok' => $result['ok'],
            'error' => $result['error'],
            'status' => $result['status'],
        ], $result['ok'] ? 200 : 400);

    case 'import_from_bot':
        if (!is_post()) {
            json_response(['ok' => false, 'error' => 'Invalid method'], 405);
        }
        $found = telegram_discover_chats();
        if (empty($found['ok'])) {
            json_response(['ok' => false, 'error' => $found['error'] ?: 'Unable to read chats from the bot.'], 400);
        }
        $added = 0;
        $skipped = 0;
        $ins = db()->prepare('INSERT INTO users (name, chat_id) VALUES (?, ?)');
        $exists = db()->prepare('SELECT id FROM users WHERE chat_id = ? LIMIT 1');
        foreach ($found['chats'] as $chat) {
            if (!validate_chat_id($chat['chat_id'])) {
                continue;
            }
            $exists->execute([$chat['chat_id']]);
            if ($exists->fetch()) {
                $skipped++;
                continue;
            }
            $ins->execute([$chat['name'], $chat['chat_id']]);
            $added++;
        }
        json_response([
            'ok' => true,
            'added' => $added,
            'skipped' => $skipped,
            'found' => count($found['chats']),
        ]);

    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}
