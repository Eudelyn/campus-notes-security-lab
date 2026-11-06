<?php
declare(strict_types=1);

if (!defined('CAMPUS_NOTES')) { http_response_code(404); exit; }

/** Escape any value for HTML text or a quoted attribute. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Start the session with hardened cookie settings. */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');   // reject unknown (attacker-chosen) session IDs
    ini_set('session.use_only_cookies', '1');  // never accept IDs from the URL
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,  // true automatically once served over HTTPS
        'httponly' => true,    // JavaScript cannot read the cookie
        'samesite' => 'Lax',   // cookie not sent on cross-site POSTs
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(mixed $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'");
    header('Referrer-Policy: same-origin');
}

/** Read a POST field as a trimmed string ('' if missing or not a string). */
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Parse a positive integer ID from a string or int; null otherwise. */
function parse_id(mixed $value): ?int
{
    if (!is_int($value) && !is_string($value)) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function text_length(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): string
{
    $message = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $message;
}

function redirect(string $path = '/'): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
