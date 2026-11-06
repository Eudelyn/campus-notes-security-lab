<?php
declare(strict_types=1);

if (!defined('CAMPUS_NOTES')) { http_response_code(404); exit; }

// Every query is scoped to the owner, so one user can never read or change another user's notes.

function notes_for_user(int $userId): array
{
    $stmt = db()->prepare('SELECT id, user_id, title, body FROM notes WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function find_note_for_user(int $noteId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT id, user_id, title, body FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$noteId, $userId]);
    return $stmt->fetch() ?: null;
}

function create_note(int $userId, string $title, string $body): void
{
    $stmt = db()->prepare('INSERT INTO notes (user_id, title, body) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $body]);
}

function update_note_for_user(int $noteId, int $userId, string $title, string $body): void
{
    $stmt = db()->prepare('UPDATE notes SET title = ?, body = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $body, $noteId, $userId]);
}

function delete_note_for_user(int $noteId, int $userId): bool
{
    $stmt = db()->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$noteId, $userId]);
    return $stmt->rowCount() > 0;
}

/** Returns an error message, or null if the title and body are acceptable. */
function validate_note(mixed $title, mixed $body): ?string
{
    if (!is_string($title) || !is_string($body)) {
        return 'Title and body must be text.';
    }
    $title = trim($title);
    if ($title === '' || text_length($title) > 120) {
        return 'Title must be 1 to 120 characters.';
    }
    if (trim($body) === '' || strlen($body) > 10000) {
        return 'Body must be 1 to 10,000 characters.';
    }
    return null;
}
