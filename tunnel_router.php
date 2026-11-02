<?php
// Used ONLY for the online-simulator demo (see WOKWI_GUIDE.md).
//
// Run it with PHP's built-in server instead of Apache:
//   C:\xampp\php\php.exe -S 127.0.0.1:8081 C:\xampp\htdocs\egg-monitor\tunnel_router.php
//
// Then point the internet tunnel at port 8081. This router lets the outside world reach
// exactly ONE file, api/telemetry.php (which still needs the secret X-API-KEY).
// Your login pages, admin pages, config.php and phpMyAdmin are NOT reachable this way.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/api/telemetry.php') {
    require __DIR__ . '/api/telemetry.php';
    return true;
}

http_response_code(404);
header('Content-Type: text/plain');
echo 'Not found';
return true;
