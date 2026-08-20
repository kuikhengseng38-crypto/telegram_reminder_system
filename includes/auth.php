<?php
/**
 * Session authentication, login throttling, password reset
 */

declare(strict_types=1);

function current_admin(bool $refresh = false): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = false;
    if ($refresh) {
        $admin = false;
    }
    if ($admin !== false) {
        return $admin;
    }
    $stmt = db()->prepare('SELECT id, username, email, is_active, last_login, created_at FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['is_active'] !== 1) {
        logout_admin();
        $admin = null;
        return null;
    }
    $admin = $row;
    return $admin;
}

function is_logged_in(): bool
{
    return current_admin() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false) {
            json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? app_url('admin/');
        redirect('admin/login.php');
    }
}

function login_admin(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['login_at'] = time();
    $stmt = db()->prepare('UPDATE admins SET last_login = ? WHERE id = ?');
    $stmt->execute([now_dt(), $adminId]);
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function client_locked_out(string $username): bool
{
    $minutes = (int) app_config('login_lock_minutes', 15);
    $max = (int) app_config('login_max_attempts', 5);
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND username = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([request_ip(), $username, $minutes]);
    return (int) $stmt->fetchColumn() >= $max;
}

function record_login_attempt(string $username): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)');
    $stmt->execute([request_ip(), $username]);
}

function clear_login_attempts(string $username): void
{
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE ip_address = ? AND username = ?');
    $stmt->execute([request_ip(), $username]);
}

function attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'Please enter username and password.'];
    }
    if (!validate_username($username)) {
        return ['ok' => false, 'error' => 'Invalid username format.'];
    }
    if (client_locked_out($username)) {
        return ['ok' => false, 'error' => 'Too many failed attempts. Please try again later.'];
    }

    $stmt = db()->prepare('SELECT id, username, password, is_active FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        record_login_attempt($username);
        write_log('auth', 'Failed login for ' . $username . ' from ' . request_ip());
        return ['ok' => false, 'error' => 'Invalid username or password.'];
    }
    if ((int) $admin['is_active'] !== 1) {
        return ['ok' => false, 'error' => 'This account is disabled.'];
    }

    clear_login_attempts($username);
    login_admin((int) $admin['id']);
    write_log('auth', 'Login success for ' . $username);
    return ['ok' => true];
}

function create_reset_token(string $identity): array
{
    $identity = trim($identity);
    $stmt = db()->prepare('SELECT id, username, email FROM admins WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $admin = $stmt->fetch();

    // Always return success to avoid account enumeration
    if (!$admin) {
        return ['ok' => true];
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token . app_config('app_key'));
    $expires = date('Y-m-d H:i:s', time() + (int) app_config('reset_expiry', 3600));

    $upd = db()->prepare('UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?');
    $upd->execute([$hash, $expires, $admin['id']]);

    $link = app_url('admin/reset-password.php?token=' . $token);
    $sent = send_reset_email($admin['email'], $admin['username'], $link);

    write_log('auth', 'Password reset requested for ' . $admin['username'] . ' email_sent=' . ($sent ? '1' : '0'));
    if (!$sent) {
        write_log('password_reset', 'Reset link for ' . $admin['username'] . ': ' . $link);
    }

    return ['ok' => true, 'emailed' => $sent];
}

function consume_reset_token(string $token, string $newPassword): array
{
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    $hash = hash('sha256', $token . app_config('app_key'));
    $stmt = db()->prepare(
        'SELECT id FROM admins WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$hash]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return ['ok' => false, 'error' => 'This reset link is invalid or has expired.'];
    }

    $upd = db()->prepare('UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
    $upd->execute([password_hash($newPassword, PASSWORD_BCRYPT), $admin['id']]);
    write_log('auth', 'Password reset completed for admin id ' . $admin['id']);
    return ['ok' => true];
}

function send_reset_email(string $to, string $username, string $link): bool
{
    $subject = 'Password reset - ' . app_config('app_name');
    $body = "Hello {$username},\n\nA password reset was requested for your admin account.\n"
        . "Open this link within 1 hour to set a new password:\n{$link}\n\n"
        . "If you did not request this, you can ignore this email.\n";
    $headers = "From: no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $body, $headers);
}
