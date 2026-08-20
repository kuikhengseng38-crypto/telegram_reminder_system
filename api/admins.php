<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$action = (string) (query('action') ?: post('action'));
$payload = is_post() ? require_json_post() : $_GET;
if ($action === '') {
    $action = (string) ($payload['action'] ?? '');
}

$me = current_admin();

switch ($action) {
    case 'save':
        $id = (int) ($payload['id'] ?? 0);
        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $active = !empty($payload['is_active']) ? 1 : 0;

        if (!validate_username($username)) {
            json_response(['ok' => false, 'error' => 'Username must be 3-50 letters, numbers, dots, dashes or underscores.']);
        }
        if (!validate_email($email)) {
            json_response(['ok' => false, 'error' => 'Enter a valid email address.']);
        }

        $dup = db()->prepare('SELECT id FROM admins WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        $dup->execute([$username, $email, $id]);
        if ($dup->fetch()) {
            json_response(['ok' => false, 'error' => 'Username or email already exists.']);
        }

        if ($id > 0) {
            if ($id === (int) $me['id']) {
                $active = 1;
            }
            if ($password !== '') {
                if (strlen($password) < 8) {
                    json_response(['ok' => false, 'error' => 'Password must be at least 8 characters.']);
                }
                $stmt = db()->prepare('UPDATE admins SET username = ?, email = ?, password = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), $active, $id]);
            } else {
                $stmt = db()->prepare('UPDATE admins SET username = ?, email = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$username, $email, $active, $id]);
            }
        } else {
            if (strlen($password) < 8) {
                json_response(['ok' => false, 'error' => 'Password must be at least 8 characters.']);
            }
            $stmt = db()->prepare('INSERT INTO admins (username, email, password, is_active) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), $active]);
            $id = (int) db()->lastInsertId();
        }
        json_response(['ok' => true, 'id' => $id]);

    case 'delete':
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1 || $id === (int) $me['id']) {
            json_response(['ok' => false, 'error' => 'You cannot delete your own account.']);
        }
        $count = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        if ($count <= 1) {
            json_response(['ok' => false, 'error' => 'At least one admin must remain.']);
        }
        db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);

    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}
