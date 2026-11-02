<?php
// Small UI helpers: the egg mark, inline icons, number formatting and the range "instrument".
// Pure PHP + inline SVG, so the site needs no JavaScript.

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// 22.0 -> "22", 22.5 -> "22.5"
function num($n, int $dec = 1): string {
    $s = number_format((float)$n, $dec, '.', '');
    return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s;
}

// 75 -> "1 minute", 4 -> "4 seconds"
function ago(int $seconds): string {
    if ($seconds < 60)   return $seconds . ($seconds === 1 ? ' second' : ' seconds');
    if ($seconds < 3600) { $m = intdiv($seconds, 60); return $m . ($m === 1 ? ' minute' : ' minutes'); }
    if ($seconds < 86400){ $hr = intdiv($seconds, 3600); return $hr . ($hr === 1 ? ' hour' : ' hours'); }
    $dy = intdiv($seconds, 86400); return $dy . ($dy === 1 ? ' day' : ' days');
}

// The egg. Its colour comes from CSS (.egg-body), so it can show status.
function egg_mark(int $width = 28, string $class = ''): string {
    $height = (int)round($width * 1.2);
    return '<svg class="egg ' . h($class) . '" width="' . $width . '" height="' . $height . '" viewBox="0 0 100 120" aria-hidden="true" focusable="false">'
         . '<path class="egg-body" d="M50 4C27 4 8 44 8 76c0 24 18 40 42 40s42-16 42-40C92 44 73 4 50 4z"/>'
         . '<path class="egg-shine" d="M29 76c0-17 6-31 15-43" fill="none" stroke-linecap="round"/>'
         . '</svg>';
}

function icon(string $name): string {
    $p = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'stock'     => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'sales'     => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14c2.7.2 4.5 2.3 4.5 5"/>',
        'sim'       => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'logout'    => '<path d="M9 4H5v16h4"/><path d="M16 8l4 4-4 4M20 12H9"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/**
 * One reading shown against its safe range.
 * The pale band is the safe range, the bar is where the current value sits.
 * $state: ok | warn | off
 */
function instrument(string $label, $value, string $unit, float $min, float $max, float $safeLo, float $safeHi,
                    int $dec, string $state, string $limitText, bool $dark = false): string {
    $span = max(0.0001, $max - $min);
    $pct  = fn(float $v): float => max(0.0, min(100.0, ($v - $min) / $span * 100));
    $has  = $value !== null && $value !== '';
    $left = $pct($safeLo);
    $wid  = max(0.0, $pct($safeHi) - $left);
    $mark = $has ? '<span class="inst-mark" style="left:' . round($pct((float)$value), 2) . '%"></span>' : '';
    $shown = $has ? number_format((float)$value, $dec, '.', '') : '--';
    $word = ['ok' => 'Inside the safe range', 'warn' => 'Outside the safe range', 'off' => 'No current reading'][$state] ?? '';

    return '<div class="inst is-' . h($state) . ($dark ? ' on-dark' : '') . '">'
        . '<div class="inst-top"><span class="inst-label">' . h($label) . '</span><span class="inst-limit">' . h($limitText) . '</span></div>'
        . '<div class="inst-value">' . h($shown) . ($unit !== '' ? '<span class="unit">' . h($unit) . '</span>' : '') . '</div>'
        . '<div class="inst-track" role="img" aria-label="' . h($label . ': ' . $shown . ' ' . $unit . '. ' . $word) . '">'
        .   '<span class="inst-safe" style="left:' . round($left, 2) . '%;width:' . round($wid, 2) . '%"></span>' . $mark
        . '</div>'
        . '<div class="inst-scale"><span>' . h(num($min, 0)) . '</span><span>' . h(num($max, 0)) . ' ' . h($unit) . '</span></div>'
        . '</div>';
}
