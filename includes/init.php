<?php
/**
 * Bootstrap: paths, config, session, PDO
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

foreach (['app', 'database', 'telegram'] as $configName) {
    $configPath = BASE_PATH . '/config/' . $configName . '.php';
    if (!is_file($configPath)) {
        $hint = 'Missing config/' . $configName . '.php. Copy config/' . $configName
            . '.example.php to config/' . $configName . '.php and fill in your values, or run install.php.';
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $hint . PHP_EOL);
            exit(1);
        }
        http_response_code(500);
        exit($hint);
    }
}

$configApp = require BASE_PATH . '/config/app.php';
$configDb  = require BASE_PATH . '/config/database.php';
$configTg  = require BASE_PATH . '/config/telegram.php';

date_default_timezone_set($configApp['timezone'] ?? 'Asia/Kuala_Lumpur');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/logs/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name($configApp['session_name'] ?? 'khs_reminder_sess');
    session_set_cookie_params([
        'lifetime' => (int) ($configApp['session_lifetime'] ?? 14400),
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require BASE_PATH . '/config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        (int) $cfg['port'],
        $cfg['dbname'],
        $cfg['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $tzName = app_config('timezone', 'Asia/Kuala_Lumpur');
        try {
            $offset = (new DateTime('now', new DateTimeZone($tzName)))->format('P');
            $pdo->exec("SET time_zone = " . $pdo->quote($offset));
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            // Keep connection even if timezone is not loadable on the host
        }
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, 'Database connection failed.' . PHP_EOL);
            exit(1);
        }
        http_response_code(500);
        exit('Database connection failed. Please check config/database.php');
    }

    return $pdo;
}

function app_config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/app.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function telegram_config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/telegram.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/telegram.php';
require_once BASE_PATH . '/includes/dispatcher.php';
require_once BASE_PATH . '/includes/templates.php';
require_once BASE_PATH . '/includes/web_cron.php';
maybe_run_web_cron();
