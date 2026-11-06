<?php
declare(strict_types=1);

if (!defined('CAMPUS_NOTES')) { http_response_code(404); exit; }

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** Returns an error message, or null on success. Role is always 'student' (never taken from the client). */
function register_user(string $email, string $password): ?string
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
        return 'Please enter a valid email address.';
    }
    if (strlen($password) < 12 || strlen($password) > 72) {
        return 'Password must be 12 to 72 characters.';
    }

    $stmt = db()->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), 'student']);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { // duplicate email
            return 'Could not create that account.';
        }
        throw $e;
    }
    return null;
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Spend similar time on unknown emails so response timing does not reveal accounts.
        password_verify($password, password_hash('timing-dummy', PASSWORD_DEFAULT));
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);      // prevent session fixation
    unset($_SESSION['csrf_token']);   // new token for the new session
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'],
    ]);
    session_destroy();
}
