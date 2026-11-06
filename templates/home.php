<?php
declare(strict_types=1);

if (!defined('CAMPUS_NOTES')) { http_response_code(404); exit; }

/** @var ?int $userId @var string $message @var array $notes @var ?array $editing */
$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="csrf-token" content="<?= e($token) ?>">
<title>Campus Notes</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<h1>Campus Notes</h1>
<?php if ($message !== ''): ?><p class="message"><?= e($message) ?></p><?php endif; ?>

<?php if ($userId === null): ?>
<h2>Register or sign in</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
  <label>Email <input name="email" type="email" required></label>
  <label>Password <input name="password" type="password" required></label>
  <button name="action" value="register">Register</button>
  <button name="action" value="login">Sign in</button>
</form>
<?php else: ?>
<form method="post" class="inline">
  User ID: <?= e($userId) ?> | Role: <?= e($_SESSION['role'] ?? '') ?> |
  <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
  <button name="action" value="logout">Log out</button>
</form>

<?php if ($editing): ?>
<h2>Edit note</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
  <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
  <label>Title <input name="title" value="<?= e($editing['title']) ?>" maxlength="120" required></label>
  <label>Body <textarea name="body" required><?= e($editing['body']) ?></textarea></label>
  <button name="action" value="update">Update</button>
  <a href="/">Cancel</a>
</form>
<?php endif; ?>

<h2>Create a note</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
  <label>Title <input name="title" maxlength="120" required></label>
  <label>Body <textarea name="body" required></textarea></label>
  <button name="action" value="save">Save</button>
</form>

<h2>Your notes</h2>
<?php foreach ($notes as $note): ?>
<article>
  <h3><?= e($note['title']) ?></h3>
  <p><?= nl2br(e($note['body'])) ?></p>
  <a href="?edit=<?= e($note['id']) ?>">Edit</a>
  <form method="post" class="inline">
    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
    <input type="hidden" name="id" value="<?= e($note['id']) ?>">
    <button name="action" value="delete">Delete</button>
  </form>
</article>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
