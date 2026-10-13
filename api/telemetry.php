<?php
// IoT Telemetry Ingestion Module: receives JSON from the NodeMCU through an HTTP POST.
//
//   POST  http://<laptop-ip>/egg-monitor/api/telemetry.php
//   Header  X-API-KEY: <same value as API_KEY in config.php>
//   Body    {"device_id":"nodemcu-01","temperature":24.5,"humidity":62.1,"air_quality":310}
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

function reply(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['ok' => false, 'error' => 'Use POST']);

// Read the API key header (works on normal XAMPP; the fallback covers other Apache setups).
$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($key === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp($name, 'X-API-KEY') === 0) { $key = $value; }
    }
}
if (!hash_equals(API_KEY, $key)) reply(401, ['ok' => false, 'error' => 'Bad API key']);

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) reply(400, ['ok' => false, 'error' => 'Body must be JSON']);

$device = trim((string)($data['device_id'] ?? ''));
$temp   = $data['temperature'] ?? null;
$hum    = $data['humidity'] ?? null;
$air    = $data['air_quality'] ?? null;

if ($device === '' || strlen($device) > 50)           reply(422, ['ok' => false, 'error' => 'device_id missing']);
if (!is_numeric($temp) || $temp < -40 || $temp > 80)  reply(422, ['ok' => false, 'error' => 'temperature invalid']);
if (!is_numeric($hum)  || $hum < 0    || $hum > 100)  reply(422, ['ok' => false, 'error' => 'humidity invalid']);
if (!is_numeric($air)  || $air < 0    || $air > 1023) reply(422, ['ok' => false, 'error' => 'air_quality invalid']);

db()->prepare('INSERT INTO telemetry_logs (device_id, temperature, humidity, air_quality) VALUES (?,?,?,?)')
    ->execute([$device, $temp, $hum, (int)$air]);

reply(201, ['ok' => true, 'message' => 'Reading saved']);
