<?php
/**
 * Telegram Bot API integration
 */

declare(strict_types=1);

/**
 * Send a Telegram text message to a chat_id.
 *
 * @return array{ok:bool, status:string, error:?string, http_code:int, raw:?array}
 */
function sendTelegramMessage(string $chat_id, string $message): array
{
    $chat_id = trim($chat_id);
    $message = trim($message);

    if (!validate_chat_id($chat_id)) {
        return [
            'ok' => false,
            'status' => 'failed',
            'error' => 'Invalid chat_id',
            'http_code' => 0,
            'raw' => null,
        ];
    }
    if ($message === '') {
        return [
            'ok' => false,
            'status' => 'failed',
            'error' => 'Message cannot be empty',
            'http_code' => 0,
            'raw' => null,
        ];
    }
    if (mb_strlen($message) > 4096) {
        $message = mb_substr($message, 0, 4096);
    }

    $token = telegram_config('bot_token');
    $base  = rtrim((string) telegram_config('api_base', 'https://api.telegram.org'), '/');
    $url   = $base . '/bot' . $token . '/sendMessage';

    $payload = [
        'chat_id' => $chat_id,
        'text'    => $message,
        'disable_web_page_preview' => true,
    ];

    $result = telegram_http_post($url, $payload);
    $ok = !empty($result['decoded']['ok']);

    if (!$ok) {
        $desc = $result['decoded']['description'] ?? ('HTTP ' . $result['http_code']);
        if (is_string($desc) && stripos($desc, 'chat not found') !== false) {
            $desc = 'Chat not found. The recipient must open @'
                . telegram_config('bot_username')
                . ' and send /start. Use their own chat_id, not a phone number or username.';
        }
        write_log('telegram', 'FAIL chat_id=' . $chat_id . ' error=' . $desc);
        return [
            'ok' => false,
            'status' => 'failed',
            'error' => is_string($desc) ? $desc : 'Telegram API error',
            'http_code' => $result['http_code'],
            'raw' => $result['decoded'],
        ];
    }

    write_log('telegram', 'OK chat_id=' . $chat_id);
    return [
        'ok' => true,
        'status' => 'sent',
        'error' => null,
        'http_code' => $result['http_code'],
        'raw' => $result['decoded'],
    ];
}

function telegram_http_post(string $url, array $payload): array
{
    $timeout = (int) app_config('telegram_timeout', 20);
    $verify = telegram_ssl_verify_enabled();

    if (function_exists('curl_init')) {
        $result = telegram_curl_post($url, $payload, $timeout, $verify);
        $sslFailed = telegram_is_ssl_error($result['curl_errno'] ?? 0, (string) ($result['curl_error'] ?? ''));
        if ($verify && $sslFailed) {
            write_log('telegram', 'SSL verify failed on this machine, retrying once without peer verification');
            $result = telegram_curl_post($url, $payload, $timeout, false);
        }
        unset($result['curl_errno'], $result['curl_error']);
        return $result;
    }

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => $timeout,
        ],
        'ssl' => [
            'verify_peer'      => $verify,
            'verify_peer_name' => $verify,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $headers = [];
    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers() ?: [];
    }
    $code = 0;
    if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $m)) {
        $code = (int) $m[1];
    }
    if ($body === false) {
        return ['http_code' => $code, 'decoded' => ['ok' => false, 'description' => 'HTTP request failed']];
    }
    $decoded = json_decode((string) $body, true);
    return [
        'http_code' => $code,
        'decoded'   => is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid JSON response'],
    ];
}

function telegram_ssl_verify_enabled(): bool
{
    $cfg = telegram_config('ssl_verify', true);
    return !($cfg === false || $cfg === 0 || $cfg === '0');
}

function telegram_is_ssl_error(int $errno, string $error): bool
{
    if (in_array($errno, [35, 51, 58, 60, 77, 82, 83], true)) {
        return true;
    }
    return stripos($error, 'ssl') !== false || stripos($error, 'certificate') !== false;
}

function telegram_curl_post(string $url, array $payload, int $timeout, bool $verify): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => $verify,
        CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        CURLOPT_USERAGENT      => 'KHS-Reminder/1.0',
    ];
    $cafile = BASE_PATH . '/config/cacert.pem';
    if ($verify && is_file($cafile)) {
        $opts[CURLOPT_CAINFO] = $cafile;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = (string) curl_error($ch);
    $errno = (int) curl_errno($ch);
    curl_close($ch);

    if ($body === false) {
        return [
            'http_code' => $code ?: 0,
            'decoded' => ['ok' => false, 'description' => $err !== '' ? $err : 'cURL error'],
            'curl_errno' => $errno,
            'curl_error' => $err,
        ];
    }
    $decoded = json_decode((string) $body, true);
    return [
        'http_code' => $code,
        'decoded'   => is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid JSON response'],
        'curl_errno' => $errno,
        'curl_error' => $err,
    ];
}

function telegram_delay(): void
{
    $ms = (int) app_config('message_delay_ms', 1000);
    if ($ms > 0) {
        usleep($ms * 1000);
    }
}

function telegram_method_url(string $method): string
{
    $token = telegram_config('bot_token');
    $base  = rtrim((string) telegram_config('api_base', 'https://api.telegram.org'), '/');
    return $base . '/bot' . $token . '/' . ltrim($method, '/');
}

/**
 * People/groups who recently messaged the bot (getUpdates).
 *
 * @return array{ok:bool, error:?string, chats:array<int, array{chat_id:string, name:string, type:string}>}
 */
function telegram_discover_chats(): array
{
    $result = telegram_http_post(telegram_method_url('getUpdates'), [
        'limit' => 100,
        'timeout' => 0,
    ]);
    if (empty($result['decoded']['ok'])) {
        $desc = $result['decoded']['description'] ?? 'Unable to read bot chats';
        return ['ok' => false, 'error' => is_string($desc) ? $desc : 'Unable to read bot chats', 'chats' => []];
    }

    $chats = [];
    foreach (($result['decoded']['result'] ?? []) as $update) {
        if (!is_array($update)) {
            continue;
        }
        $chat = $update['message']['chat']
            ?? $update['edited_message']['chat']
            ?? $update['my_chat_member']['chat']
            ?? $update['chat_member']['chat']
            ?? null;
        if (!is_array($chat) || !isset($chat['id'])) {
            continue;
        }
        $id = (string) $chat['id'];
        $name = trim((string) (
            $chat['title']
            ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''))
            ?? $chat['username']
            ?? ('User ' . $id)
        ));
        if ($name === '') {
            $name = 'User ' . $id;
        }
        $chats[$id] = [
            'chat_id' => $id,
            'name' => $name,
            'type' => (string) ($chat['type'] ?? 'private'),
        ];
    }

    return ['ok' => true, 'error' => null, 'chats' => array_values($chats)];
}
