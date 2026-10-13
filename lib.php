<?php
require_once __DIR__ . '/db.php';

function dashboard_data(): array {
    $pdo = db();
    $latest = $pdo->query('SELECT device_id, temperature, humidity, air_quality, created_at,
                                  TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
                           FROM telemetry_logs ORDER BY id DESC LIMIT 1')->fetch();
    $history = $pdo->query('SELECT temperature, humidity, air_quality, created_at
                            FROM (SELECT * FROM telemetry_logs ORDER BY id DESC LIMIT 30) t ORDER BY id ASC')->fetchAll();

    $alerts = [];
    $status = 'off';
    if ($latest) {
        $online = (int)$latest['age_seconds'] <= DEVICE_STALE_SECONDS;
        $status = $online ? 'ok' : 'off';
        if ($online) {
            if ($latest['temperature'] > TEMP_MAX) $alerts[] = "Temperature {$latest['temperature']}°C is above the safe limit (" . TEMP_MAX . "°C)";
            if ($latest['humidity'] < HUM_MIN)     $alerts[] = "Humidity {$latest['humidity']}% is below " . HUM_MIN . "%";
            if ($latest['humidity'] > HUM_MAX)     $alerts[] = "Humidity {$latest['humidity']}% is above " . HUM_MAX . "%";
            if ($latest['air_quality'] > AIR_MAX)  $alerts[] = "Air quality reading {$latest['air_quality']} is above " . AIR_MAX . " (possible ammonia / poor ventilation)";
            if ($alerts) $status = 'warn';
        }
    }
    $low = $pdo->query('SELECT batch_ref, crates_in_stock FROM stock_registers WHERE crates_in_stock <= low_stock_level ORDER BY crates_in_stock')->fetchAll();
    foreach ($low as $l) $alerts[] = "Low stock: {$l['batch_ref']} has only {$l['crates_in_stock']} crate(s)";

    $today = $pdo->query('SELECT COALESCE(SUM(total),0) t, COALESCE(SUM(crates_sold),0) c FROM sales_logs WHERE DATE(sold_at)=CURDATE()')->fetch();
    $stockTotal = (int)$pdo->query('SELECT COALESCE(SUM(crates_in_stock),0) FROM stock_registers')->fetchColumn();

    return [
        'latest' => $latest, 'history' => $history, 'status' => $status, 'alerts' => $alerts,
        'today_sales' => (float)$today['t'], 'today_crates' => (int)$today['c'], 'stock_total' => $stockTotal,
    ];
}

// Draws the chart as an SVG picture written by PHP (no JavaScript needed).
function chart_svg(array $h): string {
    $W = 900; $H = 220; $pad = 30; $n = count($h);
    $svg = '<svg viewBox="0 0 900 220" width="100%" role="img" aria-label="Temperature and humidity chart">';
    for ($i = 0; $i <= 4; $i++) {
        $y = $pad + ($H - 2 * $pad) * $i / 4;
        $svg .= sprintf('<line x1="%d" y1="%.1f" x2="890" y2="%.1f" stroke="#ddd"/><text x="2" y="%.1f" font-size="11" fill="#777">%d</text>', $pad, $y, $y, $y + 4, 100 - $i * 25);
    }
    if ($n < 2) return $svg . '<text x="350" y="110" font-size="13" fill="#777">Waiting for more readings...</text></svg>';
    foreach (['temperature' => '#d62828', 'humidity' => '#1d6fd6'] as $key => $color) {
        $pts = [];
        foreach (array_values($h) as $i => $r) {
            $x = $pad + ($W - $pad - 10) * $i / ($n - 1);
            $v = max(0, min(100, (float)$r[$key]));
            $pts[] = sprintf('%.1f,%.1f', $x, $H - $pad - ($H - 2 * $pad) * $v / 100);
        }
        $svg .= '<polyline fill="none" stroke="' . $color . '" stroke-width="2" points="' . implode(' ', $pts) . '"/>';
    }
    return $svg . '</svg>';
}
