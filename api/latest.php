<?php
// JSON feed of the dashboard data (login required). Useful to show the lecturer
// that the dashboard is "receiving API data", and for a future mobile app.
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lib.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'login required']);
    exit;
}
echo json_encode(dashboard_data());
