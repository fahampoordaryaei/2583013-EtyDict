<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../log/eventlogger.php';

function submitMessage(string $name, string $email, string $subject, string $message): bool
{

    $mysqli = getMysqli();
    $sql = 'INSERT INTO messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("submitMessage Prepare failed: " . $mysqli->error);
        $error_message = $mysqli->error;
        logEvent('contact_submit_prepare_error', 500, "Database prepare error: $error_message");
        return false;
    }

    $stmt->bind_param('ssss', $name, $email, $subject, $message);
    $result = $stmt->execute();
    if (!$result) {
        error_log("submitMessage Execute failed: " . $stmt->error);
        $error_message = $stmt->error;
        logEvent('contact_submit_execute_error', 500, "Database execute error: $error_message");
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}
