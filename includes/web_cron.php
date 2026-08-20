<?php
/**
 * Built-in PHP scheduler. Runs when someone opens the admin site.
 * No Windows Task Scheduler required.
 */

declare(strict_types=1);

function maybe_run_web_cron(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (strpos($script, '/api/') !== false || strpos($script, '/cron/') !== false) {
        return;
    }

    $stampFile = BASE_PATH . '/logs/web_cron.last';
    $last = is_file($stampFile) ? (int) trim((string) @file_get_contents($stampFile)) : 0;
    if ($last > 0 && (time() - $last) < 50) {
        return;
    }
    @file_put_contents($stampFile, (string) time(), LOCK_EX);

    $lockFile = BASE_PATH . '/logs/cron.lock';
    $lock = @fopen($lockFile, 'c+');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        return;
    }

    ignore_user_abort(true);
    @set_time_limit(90);
    try {
        $processed = process_due_reminders();
        if ($processed > 0) {
            write_log('cron', 'web_cron processed=' . $processed);
        }
    } catch (Throwable $e) {
        write_log('cron', 'web_cron ERROR ' . $e->getMessage());
    }
    flock($lock, LOCK_UN);
    fclose($lock);
}
