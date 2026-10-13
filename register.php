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
$pageTitle = 'Register';
require __DIR__ . '/partials/header.php';
?>
<div class="card narrow">
  <h2>Create account</h2>
  <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>Full name</label><input name="full_name" value="<?= e($name) ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= e($email) ?>" required>
    <label>Password (min 8 characters)</label><input type="password" name="password" required>
    <label>Confirm password</label><input type="password" name="confirm" required>
    <p><button type="submit">Register</button></p>
  </form>
  <p>Already registered? <a href="login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
