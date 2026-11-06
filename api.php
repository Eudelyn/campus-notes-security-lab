<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
// No Access-Control-Allow-* headers: the API is same-origin only.

function json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

set_exception_handler(function (Throwable $e): void {
    log_exception($e);
    json_response(500, ['error' => 'Server error']);
});

// 1. Reject requests from any other origin (cross-site reads and cookie-riding writes).
$allowedOrigin = getenv('LAB_APP_ORIGIN') ?: 'http://127.0.0.1:8000';
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin !== null && $origin !== $allowedOrigin) {
    json_response(403, ['error' => 'Origin not allowed']);
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    json_response(405, ['error' => 'Method not supported']);
}

// 2. Authentication: a signed-in session is required for every request.
start_secure_session();
$userId = current_user_id();
if ($userId === null) {
    json_response(401, ['error' => 'Authentication required']);
}

// 3. GET: list only the caller's own notes.
if ($method === 'GET') {
    if (isset($_GET['user_id'])) {
        $requested = parse_id($_GET['user_id']);
        if ($requested === null) {
            json_response(400, ['error' => 'user_id must be a positive integer']);
        }
        if ($requested !== $userId) {
            json_response(403, ['error' => 'Forbidden']);
        }
    }
    json_response(200, notes_for_user($userId));
}

// 4. POST: update one of the caller's own notes.
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== 0) {
    json_response(415, ['error' => 'Content-Type must be application/json']);
}
if (!csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(403, ['error' => 'Missing or invalid CSRF token']);
}

$raw = file_get_contents('php://input', false, null, 0, 16385);
if ($raw === false || strlen($raw) > 16384) {
    json_response(413, ['error' => 'Request body too large']);
}
try {
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    json_response(400, ['error' => 'Malformed JSON']);
}
if (!is_array($data) || array_is_list($data)) {
    json_response(400, ['error' => 'Expected a JSON object with id, title, and body']);
}
if (array_diff(array_keys($data), ['id', 'title', 'body'])) {
    json_response(400, ['error' => 'Unexpected fields']);
}

$id = parse_id($data['id'] ?? null);
if ($id === null) {
    json_response(422, ['error' => 'id must be a positive integer']);
}
$error = validate_note($data['title'] ?? null, $data['body'] ?? null);
if ($error !== null) {
    json_response(422, ['error' => $error]);
}
if (find_note_for_user($id, $userId) === null) {
    json_response(404, ['error' => 'Note not found']); // same answer whether it is missing or someone else's
}

update_note_for_user($id, $userId, trim($data['title']), $data['body']);
json_response(200, ['updated' => true, 'id' => $id]);
