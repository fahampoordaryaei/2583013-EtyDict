<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/db.php';

header('Content-Type: application/json');
$start = microtime(true);
$mysqli = getMysqli();
$ok = $mysqli->query('SELECT 1');
$latencyMs = (microtime(true) - $start) * 1000;

if ($ok === false) {
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'db' => false,
        'latency_ms' => $latencyMs,
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'db' => true,
    'latency_ms' => $latencyMs,
]);
