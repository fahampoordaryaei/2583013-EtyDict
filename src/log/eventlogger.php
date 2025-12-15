<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../api/json.php';

function logEvent(string $event, int $code, string $message): void
{
    $mysqli = getMysqli();
    $message = substr($message, 0, 1023);

    $sql = 'INSERT INTO logs (event, code, message, time)
        VALUES (?, ?, ?, NOW())';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Event Logger Prepare failed: " . $mysqli->error);
        return;
    }

    $stmt->bind_param('sis', $event, $code, $message);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
}

function giveLogEvent(string $event, int $status, string $message, ?string $detail = null, ?array $payload = null): void
{
    logEvent($event, $status, $detail ?? $message);
    giveJson($payload ?? ['error' => $message], $status);
}