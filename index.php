<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

set_exception_handler(function (Throwable $e): void {
    log_exception($e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'Something went wrong. Please try again later.';
});

start_secure_session();
send_security_headers();

// ---- State-changing actions: POST only, CSRF token required ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid or missing form token. Go back, reload the page, and try again.');
    }

    $action = post_string('action');
    $userId = current_user_id();

    if ($action === 'register') {
        $error = register_user(post_string('email'), $_POST['password'] ?? '');
        flash($error ?? 'Account created. You can sign in now.');
        redirect('/');
    }

    if ($action === 'login') {
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || !attempt_login(post_string('email'), $password)) {
            flash('Invalid email or password.');
        }
        redirect('/');
    }

    // Everything below requires a signed-in user.
    if ($userId === null) {
        flash('Please sign in first.');
        redirect('/');
    }

    if ($action === 'logout') {
        logout_user();
        redirect('/');
    }

    if ($action === 'save') {
        // Owner comes from the session, never from the form.
        $error = validate_note($_POST['title'] ?? null, $_POST['body'] ?? null);
        if ($error === null) {
            create_note($userId, post_string('title'), $_POST['body']);
        }
        flash($error ?? 'Note saved.');
        redirect('/');
    }

    if ($action === 'update') {
        $id = parse_id($_POST['id'] ?? null);
        $error = validate_note($_POST['title'] ?? null, $_POST['body'] ?? null);
        if ($id === null || find_note_for_user($id, $userId) === null) {
            $error = 'Note not found.';
        }
        if ($error === null) {
            update_note_for_user($id, $userId, post_string('title'), $_POST['body']);
        }
        flash($error ?? 'Note updated.');
        redirect('/');
    }

    if ($action === 'delete') {
        $id = parse_id($_POST['id'] ?? null);
        $deleted = $id !== null && delete_note_for_user($id, $userId);
        flash($deleted ? 'Note deleted.' : 'Note not found.');
        redirect('/');
    }

    http_response_code(400);
    exit('Unknown action.');
}

// ---- Read-only page view (GET) ----
$userId  = current_user_id();
$message = take_flash();
$notes   = [];
$editing = null;

if ($userId !== null) {
    $notes = notes_for_user($userId);
    $editId = parse_id($_GET['edit'] ?? null);
    if ($editId !== null) {
        $editing = find_note_for_user($editId, $userId);
    }
}

require __DIR__ . '/templates/home.php';
