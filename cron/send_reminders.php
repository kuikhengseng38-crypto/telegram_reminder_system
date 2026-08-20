<?php
/**
 * Optional cPanel cron dispatcher.
 * Local sending is handled by PHP when the admin site is open.
 *
 * cPanel (every 1 minute):
 *   php /home/USER/public_html/cron/send_reminders.php >/dev/null 2>&1
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/init.php';

ignore_user_abort(true);
@set_time_limit(0);

$isCli = PHP_SAPI === 'cli';
$providedKey = $isCli
    ? (string) ($argv[1] ?? getenv('CRON_KEY') ?: '')
    : (string) ($_GET['key'] ?? '');
$secret = (string) app_config('cron_secret');

if (!$isCli) {
    if ($secret === '' || !hash_equals($secret, $providedKey)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        exit;
    }
}

$lockFile = BASE_PATH . '/logs/cron.lock';
$lock = @fopen($lockFile, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    $msg = 'Another cron run is already in progress.';
    write_log('cron', $msg);
    echo $msg . PHP_EOL;
    exit;
}

fwrite($lock, (string) getmypid());

try {
    $processed = process_due_reminders();
    $msg = 'Done. reminders_processed=' . $processed;
    write_log('cron', $msg);
    if (!$isCli) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $msg . PHP_EOL;
} catch (Throwable $e) {
    write_log('cron', 'FATAL ' . $e->getMessage());
    if (!$isCli) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);
    }
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
