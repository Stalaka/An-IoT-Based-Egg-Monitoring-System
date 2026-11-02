<?php
// Public home page for visitors. Signed-in users go straight to the dashboard.
require_once __DIR__ . '/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$layout    = 'public';
$pageTitle = 'Keep eggs fresh and sales records honest';
require __DIR__ . '/partials/header.php';
?>
<section class="hero">
  <div class="wrap">
    <div>
      <h1>Keep eggs fresh and sales records honest.</h1>
      <p class="lede">A low-cost storage monitor and sales ledger for small-scale egg sellers. A sensor in the store room watches the temperature, humidity and air quality, and warns you before eggs spoil.</p>
      <div class="cta">
        <a class="btn lg" href="login.php">Log in</a>
        <a class="btn ghost lg" href="register.php">Create account</a>
      </div>
      <p class="note">Final-year project prototype. Log in to see live readings.</p>
    </div>

    <div class="plate on-ink" aria-label="Example reading">
      <div class="egg-wrap"><?= egg_mark(92, 'is-ok glow') ?></div>
      <div class="insts">
        <?= instrument('Temperature', 22.5, '°C', 0, 40, 0, TEMP_MAX, 1, 'ok', 'Safe up to ' . num(TEMP_MAX) . ' °C', true) ?>
        <?= instrument('Humidity', 65, '%', 0, 100, HUM_MIN, HUM_MAX, 0, 'ok', 'Safe between ' . num(HUM_MIN, 0) . ' and ' . num(HUM_MAX, 0) . ' %', true) ?>
        <?= instrument('Air quality', 250, '', 0, 1023, 0, AIR_MAX, 0, 'ok', 'Safe up to ' . AIR_MAX, true) ?>
      </div>
      <p class="caption">Example reading. This is not live data.</p>
    </div>
  </div>
</section>

<section class="section alt" id="problem">
  <div class="wrap">
    <h2>Small egg sellers lose money in three quiet ways.</h2>
    <div class="problems">
      <div>
        <h3>Paper records go missing</h3>
        <p>Sales and stock live in notebooks. Entries get lost, sums go wrong, and nobody notices until the cash does not add up.</p>
      </div>
      <div>
        <h3>Nobody watches the store room</h3>
        <p>Eggs spoil faster when the room is warm, humid or stuffy. Without a record of conditions, you find out when the eggs are already bad.</p>
      </div>
      <div>
        <h3>Commercial systems cost too much</h3>
        <p>Remote monitoring exists, but it is priced and built for large food suppliers, not for a stall or a small depot.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="wrap">
    <h2>From the store room to your screen in four steps.</h2>
    <ol class="steps">
      <li>
        <h3>Measure</h3>
        <p>A DHT22 sensor reads temperature and humidity. An <span class="nb">MQ-135</span> sensor reads air quality.</p>
      </li>
      <li>
        <h3>Send</h3>
        <p>An ESP8266 board sends the readings over Wi-Fi every 10 seconds, with a secret key so only your device can post.</p>
      </li>
      <li>
        <h3>Store</h3>
        <p>A PHP server checks every reading and saves it in a MySQL database.</p>
      </li>
      <li>
        <h3>Show</h3>
        <p>The dashboard compares the latest reading with the safe limits and raises an alert when one is crossed.</p>
      </li>
    </ol>
  </div>
</section>

<section class="section alt" id="inside">
  <div class="wrap">
    <h2>What is inside the system.</h2>
    <dl class="features">
      <div><dt>Live storage conditions</dt><dd>Temperature, humidity and air quality shown against their safe ranges, refreshed every 5 seconds.</dd></div>
      <div><dt>Early alerts</dt><dd>The dashboard turns red when a reading leaves its safe range, and grey when the device stops reporting.</dd></div>
      <div><dt>Sales ledger</dt><dd>Record each sale in seconds. The total is worked out for you, and sales cannot be edited afterwards.</dd></div>
      <div><dt>Stock tracking</dt><dd>Every sale lowers the stock automatically, and a low-stock warning appears before you run out.</dd></div>
      <div><dt>Separate roles</dt><dd>Vendors record sales, suppliers update stock, and admins manage people. Each sees only their own pages.</dd></div>
      <div><dt>A saved history</dt><dd>Every reading is stored in the database, and the dashboard charts the latest 30.</dd></div>
    </dl>
    <p class="built"><strong>Built with</strong> an ESP8266 NodeMCU, DHT22 and MQ-135 sensors, PHP, MySQL and Apache (XAMPP).</p>
  </div>
</section>

<section class="closing on-ink">
  <div class="wrap">
    <div>
      <h2>See it working.</h2>
      <p>Log in to open the dashboard, or create a vendor account to record sales.</p>
    </div>
    <div class="cta">
      <a class="btn lg" href="login.php">Log in</a>
      <a class="btn ghost lg" href="register.php">Create account</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/partials/footer.php';
