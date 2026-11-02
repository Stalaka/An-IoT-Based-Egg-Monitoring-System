<?php
require_once __DIR__ . '/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);   // new session id after login (stops session fixation)
        $_SESSION['user'] = [
            'id' => (int)$user['id'], 'full_name' => $user['full_name'],
            'email' => $user['email'], 'role' => $user['role'],
        ];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Wrong email or password. Check both and try again.';   // same message for both, so attackers learn nothing
}
$layout    = 'auth';
$pageTitle = 'Log in';
require __DIR__ . '/partials/header.php';
?>
<h1>Log in</h1>
<p class="sub">Use the email and password you registered with.</p>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="username" required autofocus></div>
  <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
  <button type="submit">Log in</button>
</form>
<p class="switch">New here? <a href="register.php">Create an account</a></p>
<?php require __DIR__ . '/partials/footer.php';
