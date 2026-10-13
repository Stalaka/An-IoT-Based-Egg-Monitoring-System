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
    $error = 'Wrong email or password.';   // same message for both cases, so attackers learn nothing
}
$pageTitle = 'Login';
require __DIR__ . '/partials/header.php';
?>
<div class="card narrow">
  <h2>Log in</h2>
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email</label><input type="email" name="email" required autofocus>
    <label>Password</label><input type="password" name="password" required>
    <p><button type="submit">Log in</button></p>
  </form>
  <p>No account? <a href="register.php">Register here</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
