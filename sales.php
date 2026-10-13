<?php
require_once __DIR__ . '/auth.php';
require_role(['admin', 'vendor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stockId = (int)($_POST['stock_id'] ?? 0);
    $qty     = filter_var($_POST['crates_sold'] ?? '', FILTER_VALIDATE_INT);
    $pdo = db();

    try {
        if ($qty === false || $qty < 1) throw new RuntimeException('Enter a whole number of crates (1 or more).');

        $pdo->beginTransaction();                       // all-or-nothing: sale row + stock deduction
        $st = $pdo->prepare('SELECT crates_in_stock, price_per_crate FROM stock_registers WHERE id=? FOR UPDATE');
        $st->execute([$stockId]);
        $batch = $st->fetch();
        if (!$batch) throw new RuntimeException('Choose a stock batch.');
        if ($batch['crates_in_stock'] < $qty) {
            throw new RuntimeException('Not enough stock: only ' . $batch['crates_in_stock'] . ' crate(s) left.');
        }
        $total = $qty * $batch['price_per_crate'];
        $pdo->prepare('INSERT INTO sales_logs (stock_id, user_id, crates_sold, unit_price, total) VALUES (?,?,?,?,?)')
            ->execute([$stockId, current_user()['id'], $qty, $batch['price_per_crate'], $total]);
        $pdo->prepare('UPDATE stock_registers SET crates_in_stock = crates_in_stock - ? WHERE id=?')
            ->execute([$qty, $stockId]);
        $pdo->commit();
        flash('Sale recorded: KES ' . number_format($total, 2) . '. Stock updated.');
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex->getMessage(), 'err');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('Database error while saving the sale.', 'err');
    }
    header('Location: sales.php');
    exit;
}

$batches = db()->query('SELECT id, batch_ref, crates_in_stock, price_per_crate FROM stock_registers WHERE crates_in_stock > 0 ORDER BY id')->fetchAll();
$today   = db()->query('SELECT COALESCE(SUM(total),0) t, COALESCE(SUM(crates_sold),0) c FROM sales_logs WHERE DATE(sold_at)=CURDATE()')->fetch();
$recent  = db()->query('SELECT s.id, s.sold_at, b.batch_ref, s.crates_sold, s.unit_price, s.total, u.full_name
                        FROM sales_logs s JOIN stock_registers b ON b.id=s.stock_id JOIN users u ON u.id=s.user_id
                        ORDER BY s.id DESC LIMIT 15')->fetchAll();

$pageTitle = 'Sales';
require __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2>Record a sale</h2>
  <p>Today: <strong><?= (int)$today['c'] ?></strong> crate(s), <strong>KES <?= number_format($today['t'], 2) ?></strong></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Stock batch</label>
        <select name="stock_id" required>
          <option value="">-- choose --</option>
          <?php foreach ($batches as $b): ?>
            <option value="<?= $b['id'] ?>"><?= e($b['batch_ref']) ?> (<?= $b['crates_in_stock'] ?> left @ KES <?= number_format($b['price_per_crate'], 2) ?>)</option>
          <?php endforeach; ?>
        </select></div>
      <div><label>Crates sold</label><input type="number" min="1" name="crates_sold" required></div>
    </div>
    <p><button type="submit">Save sale</button></p>
  </form>
  <small class="muted">Sales are ledger entries: they are not edited or deleted, so the records stay trustworthy.</small>
</div>

<div class="card">
  <h2>Recent sales</h2>
  <table>
    <tr><th>#</th><th>Time</th><th>Batch</th><th>Crates</th><th>Unit price</th><th>Total</th><th>Recorded by</th></tr>
    <?php foreach ($recent as $r): ?>
    <tr><td><?= $r['id'] ?></td><td><?= e($r['sold_at']) ?></td><td><?= e($r['batch_ref']) ?></td><td><?= $r['crates_sold'] ?></td>
        <td><?= number_format($r['unit_price'], 2) ?></td><td><?= number_format($r['total'], 2) ?></td><td><?= e($r['full_name']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/partials/footer.php';
