<?php

require_once __DIR__ . '/auth.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $preset = $_POST['preset'] ?? '';
    if ($preset === 'hot')         { $t = 29;   $h = 45; $a = 520; }
    elseif ($preset === 'normal')  { $t = 22.5; $h = 65; $a = 250; }
    else { $t = $_POST['temperature'] ?? ''; $h = $_POST['humidity'] ?? ''; $a = $_POST['air_quality'] ?? ''; }

    if (!is_numeric($t) || $t < -40 || $t > 80 || !is_numeric($h) || $h < 0 || $h > 100 || !is_numeric($a) || $a < 0 || $a > 1023) {
        flash('Check the values: temperature -40..80, humidity 0..100, air quality 0..1023.', 'err');
    } else {
        db()->prepare('INSERT INTO telemetry_logs (device_id, temperature, humidity, air_quality) VALUES ("simulator",?,?,?)')
            ->execute([$t, $h, (int)$a]);
        flash("Simulated reading saved: {$t}°C, {$h}%, air {$a}. Open the Dashboard to see it.");
    }
    header('Location: simulate.php');
    exit;
}
$pageTitle = 'Simulator';
require __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2>Save a simulated sensor reading</h2>
  <p><small class="muted">Data from this page is <strong>simulated</strong>, not from the sensors. The real ESP8266 sends data to api/telemetry.php.</small></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Temperature (°C)</label><input name="temperature" type="number" step="0.1" value="22.5"></div>
      <div><label>Humidity (%)</label><input name="humidity" type="number" step="0.1" value="65"></div>
      <div><label>Air quality (raw)</label><input name="air_quality" type="number" value="250"></div>
    </div>
    <p><button type="submit">Save reading</button>
       <button type="submit" name="preset" value="normal">Normal preset</button>
       <button type="submit" name="preset" value="hot">Hot / bad-air preset (triggers alerts)</button></p>
  </form>
</div>
<?php require __DIR__ . '/partials/footer.php';
