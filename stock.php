<?php

require_once __DIR__ . '/auth.php';
require_role(['admin', 'supplier']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $batch  = trim($_POST['batch_ref'] ?? '');
    $crates = filter_var($_POST['crates_in_stock'] ?? '', FILTER_VALIDATE_INT);
    $price  = filter_var($_POST['price_per_crate'] ?? '', FILTER_VALIDATE_FLOAT);
    $low    = filter_var($_POST['low_stock_level'] ?? '', FILTER_VALIDATE_INT);
    $date   = $_POST['received_date'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if ($batch === '' || $crates === false || $crates < 0 || $price === false || $price < 0
                || $low === false || $low < 0 || !$d || $d->format('Y-m-d') !== $date) {
                throw new RuntimeException('Check the fields: numbers cannot be negative and the date is required.');
            }
            if ($action === 'create') {
                db()->prepare('INSERT INTO stock_registers (batch_ref, crates_in_stock, price_per_crate, low_stock_level, received_date) VALUES (?,?,?,?,?)')
                    ->execute([$batch, $crates, $price, $low, $date]);
                flash('Stock batch added.');
            } else {
                db()->prepare('UPDATE stock_registers SET batch_ref=?, crates_in_stock=?, price_per_crate=?, low_stock_level=?, received_date=? WHERE id=?')
                    ->execute([$batch, $crates, $price, $low, $date, $id]);
                flash('Stock batch updated.');
            }
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM stock_registers WHERE id=?')->execute([$id]);
            flash('Stock batch deleted.');
        }
    } catch (PDOException $ex) {
        flash(($ex->errorInfo[1] ?? 0) == 1451
            ? 'Cannot delete: sales have been recorded against this batch. Set its crates to 0 instead.'
            : 'Database error.', 'err');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    header('Location: stock.php');
    exit;
}

$confirm = null;
if (isset($_GET['delete'])) {
    $st = db()->prepare('SELECT id, batch_ref FROM stock_registers WHERE id=?');
    $st->execute([(int)$_GET['delete']]);
    $confirm = $st->fetch() ?: null;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM stock_registers WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = db()->query('SELECT * FROM stock_registers ORDER BY id DESC')->fetchAll();

$pageTitle = 'Stock';
require __DIR__ . '/partials/header.php';
?>
<?php if ($confirm): ?>
<div class="card" style="border-left:5px solid var(--red)">
  <h2>Delete this stock batch?</h2>
  <p>You are about to delete <strong><?= e($confirm['batch_ref']) ?></strong>. This cannot be undone.</p>
  <form method="post" style="display:inline">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$confirm['id'] ?>">
    <button class="danger" type="submit">Yes, delete</button>
  </form>
  <a class="btn" href="stock.php">Cancel</a>
</div>
<?php endif; ?>

<div class="card">
  <h2><?= $edit ? 'Edit stock batch' : 'Add stock batch' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <div class="row">
      <div><label>Batch reference</label><input name="batch_ref" value="<?= e($edit['batch_ref'] ?? '') ?>" required></div>
      <div><label>Crates in stock</label><input type="number" min="0" name="crates_in_stock" value="<?= e($edit['crates_in_stock'] ?? '') ?>" required></div>
      <div><label>Price per crate (KES)</label><input type="number" step="0.01" min="0" name="price_per_crate" value="<?= e($edit['price_per_crate'] ?? '') ?>" required></div>
      <div><label>Low-stock alert at (crates)</label><input type="number" min="0" name="low_stock_level" value="<?= e($edit['low_stock_level'] ?? 5) ?>" required></div>
      <div><label>Date received</label><input type="date" name="received_date" value="<?= e($edit['received_date'] ?? date('Y-m-d')) ?>" required></div>
    </div>
    <p><button type="submit"><?= $edit ? 'Save changes' : 'Add batch' ?></button>
       <?php if ($edit): ?> <a class="btn" href="stock.php">Cancel</a><?php endif; ?></p>
  </form>
</div>

<div class="card">
  <h2>Stock register</h2>
  <table>
    <tr><th>ID</th><th>Batch</th><th>Crates</th><th>Price/crate</th><th>Alert at</th><th>Received</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): $isLow = $r['crates_in_stock'] <= $r['low_stock_level']; ?>
    <tr>
      <td><?= $r['id'] ?></td><td><?= e($r['batch_ref']) ?></td><td><?= $r['crates_in_stock'] ?></td>
      <td><?= number_format($r['price_per_crate'], 2) ?></td><td><?= $r['low_stock_level'] ?></td>
      <td><?= e($r['received_date']) ?></td>
      <td><span class="badge <?= $isLow ? 'warn' : 'ok' ?>"><?= $isLow ? 'LOW' : 'OK' ?></span></td>
      <td><a href="stock.php?edit=<?= $r['id'] ?>">Edit</a> | <a href="stock.php?delete=<?= $r['id'] ?>">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/partials/footer.php';
