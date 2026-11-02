<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';
require_login();

$d = dashboard_data();
$l = $d['latest'];
$state = $d['status'];                       // ok | warn | off

// One plain sentence that tells the story of the store room.
if (!$l) {
    $headline = 'No readings yet.';
    $sub      = 'Start the device and its first reading will appear here.';
} elseif (!$d['online']) {
    $headline = 'The device is offline.';
    $sub      = 'Check its power and Wi-Fi.';
} elseif ($d['storage_alerts']) {
    $n = $d['storage_alerts'];
    $headline = 'Storage conditions need attention.';
    $sub      = $n . ($n === 1 ? ' reading is' : ' readings are') . ' outside the safe range.';
} elseif ($d['stock_alerts']) {
    $headline = 'Storage is safe. Stock is running low.';
    $sub      = 'All sensor readings are inside their safe ranges.';
} else {
    $headline = 'Storage conditions are safe.';
    $sub      = 'All readings are inside their safe ranges.';
}
$pill = ['ok' => 'All clear', 'warn' => 'Attention needed', 'off' => 'Offline'][$state];
$simulated = $l && ($l['device_id'] === 'simulator' || strpos($l['device_id'], 'wokwi') === 0);

$st = fn(string $flag): string => $d['online'] ? ($d['flags'][$flag] ? 'warn' : 'ok') : 'off';
$v  = fn(string $k) => $l ? $l[$k] : null;

$headExtra = '<meta http-equiv="refresh" content="5">';   // the page reloads itself every 5 seconds
$pageTitle = 'Dashboard';
$pageSub   = 'Latest readings from the store room. This page refreshes every 5 seconds.';
require __DIR__ . '/partials/header.php';
?>
<section class="band is-<?= h($state) ?>" aria-live="polite">
  <?= egg_mark(78, 'glow is-' . $state) ?>
  <div>
    <span class="pill"><?= h($pill) ?></span>
    <h2><?= h($headline) ?></h2>
    <p class="meta">
      <?= h($sub) ?>
      <?php if ($l): ?>Device <?= h($l['device_id']) ?>, last reading <?= h(ago((int)$l['age_seconds'])) ?> ago.<?php endif; ?>
      <?php if ($simulated): ?><span class="tag">Simulated data</span><?php endif; ?>
    </p>
    <?php if ($d['alerts']): ?>
    <ul class="alert-list">
      <?php foreach ($d['alerts'] as $i => $a): ?>
        <li class="<?= $i >= $d['storage_alerts'] ? 'stock' : '' ?>"><?= h($a) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>

<div class="insts">
  <?= instrument('Temperature', $v('temperature'), '°C', 0, 40, 0, TEMP_MAX, 1, $st('temperature'), 'Safe up to ' . num(TEMP_MAX) . ' °C') ?>
  <?= instrument('Humidity', $v('humidity'), '%', 0, 100, HUM_MIN, HUM_MAX, 1, $st('humidity'), 'Safe between ' . num(HUM_MIN, 0) . ' and ' . num(HUM_MAX, 0) . ' %') ?>
  <?= instrument('Air quality (raw)', $v('air_quality'), '', 0, 1023, 0, AIR_MAX, 0, $st('air'), 'Safe up to ' . AIR_MAX) ?>
</div>

<div class="two">
  <?= line_chart($d['history'], 'temperature', 'Temperature', '°C, last ' . count($d['history']) . ' readings', '#11203b', null, TEMP_MAX) ?>
  <?= line_chart($d['history'], 'humidity', 'Humidity', '%, last ' . count($d['history']) . ' readings', '#146b5a', HUM_MIN, HUM_MAX) ?>
</div>

<dl class="ledger">
  <div><dt>Crates in stock</dt><dd><?= (int)$d['stock_total'] ?></dd></div>
  <div><dt>Sales today</dt><dd><small class="pre">KES</small><?= number_format($d['today_sales']) ?></dd></div>
  <div><dt>Crates sold today</dt><dd><?= (int)$d['today_crates'] ?></dd></div>
</dl>

<section class="card">
  <h2>Recent readings</h2>
  <?php if (!$d['recent']): ?>
    <p class="empty">No readings saved yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Temp (°C)</th><th>Humidity (%)</th><th>Air quality</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($d['recent'] as $r):
          $bad = $r['temperature'] > TEMP_MAX || $r['humidity'] < HUM_MIN || $r['humidity'] > HUM_MAX || $r['air_quality'] > AIR_MAX; ?>
        <tr>
          <td title="<?= h($r['created_at']) ?>"><?= h(substr($r['created_at'], 11, 8)) ?></td>
          <td><?= h(num($r['temperature'])) ?></td>
          <td><?= h(num($r['humidity'])) ?></td>
          <td><?= (int)$r['air_quality'] ?></td>
          <td><span class="badge <?= $bad ? 'warn' : 'ok' ?>"><?= $bad ? 'Check' : 'Safe' ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php';
