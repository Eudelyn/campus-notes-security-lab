<?php
declare(strict_types=1);

if (!defined('CAMPUS_NOTES')) { http_response_code(404); exit; }

/**
 * Single shared PDO connection.
 * Credentials come only from environment variables: no root fallback, nothing in the repo.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $user = getenv('LAB_DB_USER');
    $pass = getenv('LAB_DB_PASSWORD');
    if ($user === false || $user === '' || $pass === false) {
        throw new RuntimeException('Database credentials are not configured (set LAB_DB_USER and LAB_DB_PASSWORD).');
    }
    $host = getenv('LAB_DB_HOST') ?: 'localhost';

    $pdo = new PDO("mysql:host={$host};dbname=notes_lab;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real server-side prepared statements
    ]);
    return $pdo;
}
