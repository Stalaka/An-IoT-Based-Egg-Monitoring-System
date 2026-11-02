<?php
require_once __DIR__ . '/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name    = trim($_POST['full_name'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || strlen($name) > 100)        $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pass) < 8)                          $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $confirm)                         $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            // Public sign-up ALWAYS creates a vendor. Only an admin can create other roles.
            $ins = db()->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "vendor")');
            $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('Account created. You can now log in.');
            header('Location: login.php');
            exit;
        }
    }
}
$layout    = 'auth';
$pageTitle = 'Create account';
require __DIR__ . '/partials/header.php';
?>
<h1>Create an account</h1>
<p class="sub">New accounts are vendor accounts. An admin can change your role later.</p>
<?php foreach ($errors as $er): ?><div class="flash err" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" autocomplete="off">
  <?= csrf_field() ?>
  <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= e($name) ?>" required></div>
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($email) ?>" required></div>
  <div class="field"><label for="password">Password (at least 8 characters)</label><input id="password" type="password" name="password" autocomplete="new-password" required></div>
  <div class="field"><label for="confirm">Confirm password</label><input id="confirm" type="password" name="confirm" autocomplete="new-password" required></div>
  <button type="submit">Create account</button>
</form>
<p class="switch">Already registered? <a href="login.php">Log in</a></p>
<?php require __DIR__ . '/partials/footer.php';
