<?php
/**
 * CSRF token helpers
 */

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token = null): bool
{
    $token = $token ?? ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function csrf_verify_request(): void
{
    if (!csrf_verify()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_response(['ok' => false, 'error' => 'Invalid security token. Please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('Invalid security token. Please refresh the page.');
    }
}
