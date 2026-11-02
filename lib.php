<?php
// Dashboard data and charts, shared by dashboard.php and api/latest.php.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ui.php';

function dashboard_data(): array {
    $pdo = db();
    $latest = $pdo->query('SELECT device_id, temperature, humidity, air_quality, created_at,
                                  TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
                           FROM telemetry_logs ORDER BY id DESC LIMIT 1')->fetch();
    $history = $pdo->query('SELECT temperature, humidity, air_quality, created_at
                            FROM (SELECT * FROM telemetry_logs ORDER BY id DESC LIMIT 30) t ORDER BY id ASC')->fetchAll();

    $alerts = [];
    $status = 'off';
    $online = false;
    $flags  = ['temperature' => false, 'humidity' => false, 'air' => false];

    if ($latest) {
        $online = (int)$latest['age_seconds'] <= DEVICE_STALE_SECONDS;
        $status = $online ? 'ok' : 'off';
        if ($online) {
            $flags['temperature'] = $latest['temperature'] > TEMP_MAX;
            $flags['humidity']    = $latest['humidity'] < HUM_MIN || $latest['humidity'] > HUM_MAX;
            $flags['air']         = $latest['air_quality'] > AIR_MAX;

            if ($latest['temperature'] > TEMP_MAX)  $alerts[] = "Temperature {$latest['temperature']}°C is above the safe limit (" . TEMP_MAX . "°C)";
            if ($latest['humidity'] < HUM_MIN)      $alerts[] = "Humidity {$latest['humidity']}% is below " . HUM_MIN . "%";
            if ($latest['humidity'] > HUM_MAX)      $alerts[] = "Humidity {$latest['humidity']}% is above " . HUM_MAX . "%";
            if ($latest['air_quality'] > AIR_MAX)   $alerts[] = "Air quality reading {$latest['air_quality']} is above " . AIR_MAX . " (possible ammonia / poor ventilation)";
            if ($alerts) $status = 'warn';
        }
    }
    $storageAlerts = count($alerts);

    $low = $pdo->query('SELECT batch_ref, crates_in_stock FROM stock_registers WHERE crates_in_stock <= low_stock_level ORDER BY crates_in_stock')->fetchAll();
    foreach ($low as $l) $alerts[] = "Low stock: {$l['batch_ref']} has only {$l['crates_in_stock']} crate(s)";

    $today = $pdo->query('SELECT COALESCE(SUM(total),0) t, COALESCE(SUM(crates_sold),0) c FROM sales_logs WHERE DATE(sold_at)=CURDATE()')->fetch();
    $stockTotal = (int)$pdo->query('SELECT COALESCE(SUM(crates_in_stock),0) FROM stock_registers')->fetchColumn();

    return [
        'latest' => $latest, 'history' => $history, 'status' => $status, 'alerts' => $alerts,
        'online' => $online, 'flags' => $flags,
        'storage_alerts' => $storageAlerts, 'stock_alerts' => count($low),
        'recent' => array_reverse(array_slice($history, -8)),
        'today_sales' => (float)$today['t'], 'today_crates' => (int)$today['c'], 'stock_total' => $stockTotal,
    ];
}

// A "nice" step for axis ticks (1, 2, 5 x 10^n).
function nice_step(float $raw): float {
    if ($raw <= 0) return 1.0;
    $mag  = pow(10, floor(log10($raw)));
    $norm = $raw / $mag;
    $step = $norm <= 1 ? 1 : ($norm <= 2 ? 2 : ($norm <= 5 ? 5 : 10));
    return $step * $mag;
}

/**
 * A line chart drawn by PHP as SVG (no JavaScript).
 * Dashed red lines show the safe limits from config.php.
 * It is drawn twice, a wide version and a narrow one; CSS shows the one that fits the screen,
 * so the text stays readable on a phone.
 */
function line_chart(array $rows, string $key, string $title, string $unit, string $color,
                    ?float $limitLo = null, ?float $limitHi = null): string {
    $head = '<section class="card chart"><h3>' . h($title) . '<span class="unit">' . h($unit) . '</span></h3>';
    $n = count($rows);
    if ($n < 2) {
        return $head . '<p class="empty">The chart appears after two readings arrive.</p></section>';
    }

    $vals = array_map(fn($r) => (float)$r[$key], $rows);
    $lo = min($vals); $hi = max($vals);
    foreach ([$limitLo, $limitHi] as $lim) { if ($lim !== null) { $lo = min($lo, $lim); $hi = max($hi, $lim); } }
    if ($hi - $lo < 4) { $mid = ($hi + $lo) / 2; $lo = $mid - 2; $hi = $mid + 2; }
    $pad  = ($hi - $lo) * 0.08;
    $step = nice_step(($hi - $lo + 2 * $pad) / 4);
    $axMin = floor(($lo - $pad) / $step) * $step;
    $axMax = ceil(($hi + $pad) / $step) * $step;

    $draw = function (int $W, int $H, string $cls) use ($rows, $vals, $n, $axMin, $axMax, $step, $color, $limitLo, $limitHi, $title): string {
        $pl = 46; $pr = 16; $pt = 14; $pb = 30;
        $x = fn(int $i): float => $pl + ($W - $pl - $pr) * $i / ($n - 1);
        $y = fn(float $v): float => $pt + ($H - $pt - $pb) * (1 - ($v - $axMin) / ($axMax - $axMin));
        $fmt = fn(float $v): string => $step < 1 ? number_format($v, 1) : number_format($v, 0);

        $svg = '<svg class="' . $cls . '" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . h($title . ' over the last ' . $n . ' readings') . '">';
        for ($t = $axMin; $t <= $axMax + 1e-9; $t += $step) {
            $yy = round($y($t), 1);
            $svg .= '<line class="grid-line" x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $yy . '" y2="' . $yy . '"/>'
                  . '<text class="ax" x="' . ($pl - 8) . '" y="' . ($yy + 4) . '" text-anchor="end">' . $fmt($t) . '</text>';
        }
        foreach ([[$limitHi, 'upper limit'], [$limitLo, 'lower limit']] as [$lim, $word]) {
            if ($lim === null) continue;
            $yy = round($y($lim), 1);
            $svg .= '<line class="lim" x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $yy . '" y2="' . $yy . '"/>'
                  . '<text class="lim-label" x="' . ($pl + 6) . '" y="' . ($yy - 6) . '">' . h($word . ' ' . num($lim, 1)) . '</text>';
        }
        $pts = [];
        foreach ($vals as $i => $v) $pts[] = round($x($i), 1) . ',' . round($y($v), 1);
        $baseY = round($y($axMin), 1);
        $svg .= '<polygon class="area" fill="' . $color . '" points="' . round($x(0), 1) . ',' . $baseY . ' ' . implode(' ', $pts) . ' ' . round($x($n - 1), 1) . ',' . $baseY . '"/>';
        $svg .= '<polyline class="ln" stroke="' . $color . '" points="' . implode(' ', $pts) . '"/>';
        $svg .= '<circle cx="' . round($x($n - 1), 1) . '" cy="' . round($y($vals[$n - 1]), 1) . '" r="4.5" fill="' . $color . '" stroke="#fff" stroke-width="2"/>';
        $svg .= '<text class="ax" x="' . $pl . '" y="' . ($H - 8) . '">' . h(substr($rows[0]['created_at'], 11, 8)) . '</text>';
        $svg .= '<text class="ax" x="' . ($W - $pr) . '" y="' . ($H - 8) . '" text-anchor="end">' . h(substr($rows[$n - 1]['created_at'], 11, 8)) . '</text>';
        return $svg . '</svg>';
    };

    return $head . $draw(640, 250, 'svg-wide') . $draw(340, 240, 'svg-narrow') . '</section>';
}
