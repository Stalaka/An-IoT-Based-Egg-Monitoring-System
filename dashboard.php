<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';
require_login();
$d = dashboard_data();
$l = $d['latest'];
$labels = ['ok' => 'ALL OK', 'warn' => 'ALERT', 'off' => $l ? 'DEVICE OFFLINE' : 'NO DATA YET'];
$headExtra = '<meta http-equiv="refresh" content="5">';   // page reloads itself every 5 seconds
$pageTitle = 'Dashboard';
require __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2>Storage room status <span class="badge <?= e($d['status']) ?>"><?= e($labels[$d['status']]) ?></span></h2>
  <small class="muted"><?= $l ? 'Device ' . e($l['device_id']) . ' - last reading ' . e($l['created_at']) . ' (' . (int)$l['age_seconds'] . 's ago)' : 'No reading yet.' ?></small>
  <div class="grid" style="margin-top:12px">
    <div class="metric"><div class="val"><?= $l ? number_format($l['temperature'], 1) : '--' ?></div><div class="lbl">Temperature (°C)</div></div>
    <div class="metric"><div class="val"><?= $l ? number_format($l['humidity'], 1) : '--' ?></div><div class="lbl">Humidity (%)</div></div>
    <div class="metric"><div class="val"><?= $l ? (int)$l['air_quality'] : '--' ?></div><div class="lbl">Air quality (raw MQ-135)</div></div>
  </div>
</div>

<div class="card">
  <h3>Alerts</h3>
  <ul class="alertlist">
    <?php if (!$d['alerts']): ?><li style="color:#116a5e">No alerts.</li><?php endif; ?>
    <?php foreach ($d['alerts'] as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
  </ul>
</div>

<div class="grid">
  <div class="card metric"><div class="val"><?= $d['stock_total'] ?></div><div class="lbl">Total crates in stock</div></div>
  <div class="card metric"><div class="val"><?= number_format($d['today_sales']) ?></div><div class="lbl">Today's sales (KES)</div></div>
  <div class="card metric"><div class="val"><?= $d['today_crates'] ?></div><div class="lbl">Crates sold today</div></div>
</div>

<div class="card">
  <h3>Last 30 readings <small class="muted">(red = temperature, blue = humidity)</small></h3>
  <?= chart_svg($d['history']) ?>
</div>
<?php require __DIR__ . '/partials/footer.php';
