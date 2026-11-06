<?php
declare(strict_types=1);

// Shared setup for every entry point (index.php, api.php).
define('CAMPUS_NOTES', true);

// Never show raw errors to users; log them instead (php -S prints the log in the terminal).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/db.php';
require __DIR__ . '/security.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/notes.php';

/** Log an exception without request data, passwords, or tokens. */
function log_exception(Throwable $e): void
{
    error_log(sprintf(
        'Campus Notes %s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    ));
}
