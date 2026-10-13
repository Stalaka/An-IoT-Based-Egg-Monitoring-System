<?php

require_once __DIR__ . '/auth.php';
require_role(['admin']);

$roles = ['admin', 'vendor', 'supplier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['full_name'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $role   = $_POST['role'] ?? 'vendor';
    $pass   = $_POST['password'] ?? '';

    try {
        if ($action === 'create') {
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || !in_array($role, $roles, true)) {
                throw new RuntimeException('Fill every field. Password needs 8+ characters.');
            }
            db()->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            flash('User added.');
        } elseif ($action === 'update') {
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $roles, true)) {
                throw new RuntimeException('Name, valid email and role are required.');
            }
            if ($id === current_user()['id'] && $role !== 'admin') {
                throw new RuntimeException('You cannot remove your own admin role.');
            }
            if ($pass !== '' && strlen($pass) < 8) {
                throw new RuntimeException('New password needs 8+ characters.');
            }
            db()->prepare('UPDATE users SET full_name=?, email=?, role=? WHERE id=?')->execute([$name, $email, $role, $id]);
            if ($pass !== '') {
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
            }
            flash('User updated.');
        } elseif ($action === 'delete') {
            if ($id === current_user()['id']) throw new RuntimeException('You cannot delete your own account.');
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            flash('User deleted.');
        }
    } catch (PDOException $ex) {
        $code = $ex->errorInfo[1] ?? 0;
        flash($code == 1062 ? 'That email already exists.'
            : ($code == 1451 ? 'Cannot delete: this user has recorded sales.' : 'Database error.'), 'err');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    header('Location: users.php');
    exit;
}

// "Are you sure?" step for delete (done with a normal page, no JavaScript).
$confirm = null;
if (isset($_GET['delete'])) {
    $st = db()->prepare('SELECT id, full_name FROM users WHERE id=?');
    $st->execute([(int)$_GET['delete']]);
    $confirm = $st->fetch() ?: null;
    if ($confirm && (int)$confirm['id'] === current_user()['id']) $confirm = null;
}

// Load a row into the form when "Edit" is clicked.
$edit = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$users = db()->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY id')->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/partials/header.php';
?>
<?php if ($confirm): ?>
<div class="card" style="border-left:5px solid var(--red)">
  <h2>Delete this user?</h2>
  <p>You are about to delete <strong><?= e($confirm['full_name']) ?></strong>. This cannot be undone.</p>
  <form method="post" style="display:inline">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$confirm['id'] ?>">
    <button class="danger" type="submit">Yes, delete</button>
  </form>
  <a class="btn" href="users.php">Cancel</a>
</div>
<?php endif; ?>

<div class="card">
  <h2><?= $edit ? 'Edit user' : 'Add user' ?></h2>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <div class="row">
      <div><label>Full name</label><input name="full_name" value="<?= e($edit['full_name'] ?? '') ?>" required></div>
      <div><label>Email</label><input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required></div>
      <div><label>Role</label>
        <select name="role">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r ?>" <?= (($edit['role'] ?? 'vendor') === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label><?= $edit ? 'New password (leave blank to keep)' : 'Password (min 8)' ?></label>
        <input type="password" name="password" <?= $edit ? '' : 'required' ?>></div>
    </div>
    <p><button type="submit"><?= $edit ? 'Save changes' : 'Add user' ?></button>
       <?php if ($edit): ?> <a class="btn" href="users.php">Cancel</a><?php endif; ?></p>
  </form>
</div>

<div class="card">
  <h2>All users</h2>
  <table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td>
      <td><?= e($u['role']) ?></td><td><?= e($u['created_at']) ?></td>
      <td>
        <a href="users.php?edit=<?= $u['id'] ?>">Edit</a>
        <?php if ($u['id'] !== current_user()['id']): ?> | <a href="users.php?delete=<?= $u['id'] ?>">Delete</a><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/partials/footer.php';
