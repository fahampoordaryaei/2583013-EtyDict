<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../log/eventlogger.php';
require_once __DIR__ . '/json.php';

setSecurityHeaders();

$event = $_POST['event'] ?? '';
$code = intval($_POST['code'] ?? 0);
$message = $_POST['message'] ?? '';

if ($event === '' || $code === 0 || $message === '') {
    http_response_code(400);
    giveLogEvent('log_event_api_missing_fields', 400, 'Log Event API: missing required fields');
    exit;
}

http_response_code(200);
logEvent($event, $code, $message);
giveJson(['status' => 'success']);
exit;
