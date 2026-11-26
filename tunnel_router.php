<?php


$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/api/telemetry.php') {
    require __DIR__ . '/api/telemetry.php';
    return true;
}

http_response_code(404);
header('Content-Type: text/plain');
echo 'Not found';
return true;
