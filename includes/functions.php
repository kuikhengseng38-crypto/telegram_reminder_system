<?php
/**
 * Shared helpers: escaping, URLs, validation, logging, JSON
 */

declare(strict_types=1);

if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null)
    {
        return strlen((string) $string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null)
    {
        $string = (string) $string;
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $root = str_replace('\\', '/', (string) realpath(BASE_PATH));
    $doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $base = '';
    if ($doc !== '' && $root !== '' && strpos($root, $doc) === 0) {
        $base = substr($root, strlen($doc));
    }
    $base = rtrim(str_replace('\\', '/', $base), '/');
    $path = ltrim($path, '/');

    return $scheme . '://' . $host . $base . ($path !== '' ? '/' . $path : '/');
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . app_url($path));
    }
    exit;
}

function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function now_dt(): string
{
    return date('Y-m-d H:i:s');
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function query(string $key, $default = '')
{
    return $_GET[$key] ?? $default;
}

function write_log(string $channel, string $message): void
{
    $dir = BASE_PATH . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '', $channel) . '.log';
    $line = '[' . now_dt() . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function validate_chat_id(string $chatId): bool
{
    return (bool) preg_match('/^-?\d{5,20}$/', trim($chatId));
}

function validate_username(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username);
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function status_label(string $status): string
{
    $map = [
        'pending'  => 'Pending',
        'sending'  => 'Sending',
        'sent'     => 'Sent',
        'failed'   => 'Failed',
        'partial'  => 'Partially Sent',
    ];
    return $map[$status] ?? $status;
}

function status_badge_class(string $status): string
{
    $map = [
        'pending'  => 'badge-pending',
        'sending'  => 'badge-sending',
        'sent'     => 'badge-sent',
        'failed'   => 'badge-failed',
        'partial'  => 'badge-partial',
    ];
    return $map[$status] ?? 'badge-pending';
}

function truncate_text(string $text, int $limit = 80): string
{
    $text = trim($text);
    $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($len <= $limit) {
        return $text;
    }
    $cut = function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    return $cut . '…';
}

function require_json_post(): array
{
    if (!is_post()) {
        json_response(['ok' => false, 'error' => 'Invalid request method'], 405);
    }
    $raw = file_get_contents('php://input');
    $json = json_decode((string) $raw, true);
    $data = (is_array($json) && $json !== []) ? $json : $_POST;
    $token = isset($data['_csrf']) ? (string) $data['_csrf'] : null;
    if (!csrf_verify($token)) {
        json_response(['ok' => false, 'error' => 'Invalid security token. Please refresh the page.'], 419);
    }
    return $data;
}
