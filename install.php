<?php
/**
 * One-time installer for cPanel / shared hosting.
 * Delete this file after a successful install.
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);
$lockFile = BASE_PATH . '/config/installed.lock';

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function write_php_config(string $path, array $data): void
{
    $export = var_export($data, true);
    $code = "<?php\nreturn {$export};\n";
    if (file_put_contents($path, $code, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write ' . $path);
    }
}

$errors = [];
$success = false;
$checks = [
    'php' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'pdo' => extension_loaded('pdo_mysql'),
    'json' => extension_loaded('json'),
    'writable_config' => is_writable(BASE_PATH . '/config') || is_writable(BASE_PATH),
    'writable_logs' => is_dir(BASE_PATH . '/logs') ? is_writable(BASE_PATH . '/logs') : @mkdir(BASE_PATH . '/logs', 0750, true),
];

if (is_file($lockFile)) {
    $installed = true;
} else {
    $installed = false;
}

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $tz = trim((string) ($_POST['timezone'] ?? 'Asia/Kuala_Lumpur'));
    $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    $botToken = trim((string) ($_POST['bot_token'] ?? ''));
    $botName = trim((string) ($_POST['bot_username'] ?? 'YourBotUsername'));
    $cronSecret = trim((string) ($_POST['cron_secret'] ?? ''));
    if ($cronSecret === '') {
        $cronSecret = bin2hex(random_bytes(16));
    }

    if ($dbName === '' || $dbUser === '') {
        $errors[] = 'Database name and username are required.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $adminUser)) {
        $errors[] = 'Admin username is invalid.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email is invalid.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }
    if ($botToken === '' || !preg_match('/^\d+:[A-Za-z0-9_-]+$/', $botToken)) {
        $errors[] = 'Bot token looks invalid.';
    }
    if (@timezone_open($tz) === false) {
        $errors[] = 'Unknown timezone.';
    }

    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $sql = file_get_contents(BASE_PATH . '/database/schema.sql');
            $sql = str_replace("\r\n", "\n", (string) $sql);
            $sql = preg_replace('/CREATE DATABASE[\s\S]*?;/i', '', $sql);
            $sql = preg_replace('/USE\s+`?[\w]+`?\s*;/i', '', $sql);

            foreach (preg_split('/;\s*\n/', $sql) as $statement) {
                $statement = trim($statement);
                $bare = trim(preg_replace('/^--.*$/m', '', $statement));
                if ($bare === '') {
                    continue;
                }
                $pdo->exec($statement);
            }

            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $existing = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($existing > 0) {
                $upd = $pdo->prepare('UPDATE admins SET username = ?, email = ?, password = ? WHERE id = 1');
                $upd->execute([$adminUser, $adminEmail, $hash]);
            } else {
                $ins = $pdo->prepare('INSERT INTO admins (username, email, password, is_active) VALUES (?, ?, ?, 1)');
                $ins->execute([$adminUser, $adminEmail, $hash]);
            }

            $appKey = bin2hex(random_bytes(32));
            write_php_config(BASE_PATH . '/config/database.php', [
                'host' => $dbHost,
                'port' => $dbPort,
                'dbname' => $dbName,
                'username' => $dbUser,
                'password' => $dbPass,
                'charset' => 'utf8mb4',
            ]);
            write_php_config(BASE_PATH . '/config/app.php', [
                'app_name' => 'Telegram Reminder System',
                'app_short' => 'KHS Reminder',
                'timezone' => $tz,
                'app_key' => $appKey,
                'cron_secret' => $cronSecret,
                'session_name' => 'khs_reminder_sess',
                'session_lifetime' => 14400,
                'reset_expiry' => 3600,
                'login_max_attempts' => 5,
                'login_lock_minutes' => 15,
                'message_delay_ms' => 1000,
                'telegram_timeout' => 20,
            ]);
            write_php_config(BASE_PATH . '/config/telegram.php', [
                'bot_token' => $botToken,
                'bot_username' => ltrim($botName, '@'),
                'api_base' => 'https://api.telegram.org',
            ]);

            file_put_contents($lockFile, date('c') . PHP_EOL);
            $success = true;
            $installed = true;
        } catch (Throwable $e) {
            $errors[] = 'Install failed: ' . $e->getMessage();
        }
    }
}

$defaults = [
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'telegram_reminder',
    'db_user' => 'root',
    'timezone' => 'Asia/Kuala_Lumpur',
    'admin_user' => 'admin',
    'admin_email' => 'admin@example.com',
    'bot_token' => '',
    'bot_username' => 'YourBotUsername',
    'cron_secret' => '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install · Telegram Reminder System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0b1a2b; color: #102033; font-family: "Segoe UI", sans-serif; }
        .wrap { max-width: 860px; margin: 40px auto; background: #fff; border-radius: 20px; padding: 32px; }
        h1 { font-size: 1.6rem; }
        .ok { color: #059669; } .bad { color: #dc2626; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Telegram Reminder System installer</h1>
    <p class="text-muted">cPanel-friendly setup. Create the MySQL database in cPanel first, then complete this form.</p>

    <ul>
        <li class="<?= $checks['php'] ? 'ok' : 'bad' ?>">PHP 7.4+ (<?= h(PHP_VERSION) ?>)</li>
        <li class="<?= $checks['pdo'] ? 'ok' : 'bad' ?>">PDO MySQL</li>
        <li class="<?= $checks['json'] ? 'ok' : 'bad' ?>">JSON</li>
        <li class="<?= $checks['writable_config'] ? 'ok' : 'bad' ?>">config/ writable</li>
        <li class="<?= $checks['writable_logs'] ? 'ok' : 'bad' ?>">logs/ writable</li>
    </ul>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Installation completed. Sign in at <a href="admin/login.php">admin/login.php</a>
            then delete <code>install.php</code>.
        </div>
    <?php elseif ($installed): ?>
        <div class="alert alert-warning">Already installed. Delete <code>install.php</code> and use the login page.</div>
        <a class="btn btn-primary" href="admin/login.php">Go to login</a>
    <?php else: ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= h($err) ?></div>
        <?php endforeach; ?>
        <form method="post">
            <h2 class="h5 mt-4">Database</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Host</label><input class="form-control" name="db_host" value="<?= h($_POST['db_host'] ?? $defaults['db_host']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Port</label><input class="form-control" name="db_port" value="<?= h($_POST['db_port'] ?? $defaults['db_port']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Database</label><input class="form-control" name="db_name" value="<?= h($_POST['db_name'] ?? $defaults['db_name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="db_user" value="<?= h($_POST['db_user'] ?? $defaults['db_user']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="db_pass"></div>
            </div>
            <h2 class="h5 mt-4">Admin &amp; Telegram</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="<?= h($_POST['timezone'] ?? $defaults['timezone']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Admin username</label><input class="form-control" name="admin_user" value="<?= h($_POST['admin_user'] ?? $defaults['admin_user']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Admin email</label><input class="form-control" type="email" name="admin_email" value="<?= h($_POST['admin_email'] ?? $defaults['admin_email']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Admin password</label><input class="form-control" type="password" name="admin_pass" required minlength="8"></div>
                <div class="col-md-6"><label class="form-label">Bot token</label><input class="form-control" name="bot_token" value="<?= h($_POST['bot_token'] ?? $defaults['bot_token']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Bot username</label><input class="form-control" name="bot_username" value="<?= h($_POST['bot_username'] ?? $defaults['bot_username']) ?>"></div>
                <div class="col-md-12"><label class="form-label">Cron secret key</label><input class="form-control" name="cron_secret" value="<?= h($_POST['cron_secret'] ?? $defaults['cron_secret']) ?>"></div>
            </div>
            <button class="btn btn-primary mt-4" type="submit">Install</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
