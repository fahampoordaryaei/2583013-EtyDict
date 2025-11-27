<?php

declare(strict_types=1);

require_once __DIR__ . '/repo/user_repo.php';
require_once __DIR__ . '/repo/dict_repo.php';

function logView(?int $userId = null, string $word): void
{
    $mysqli = getMysqli();

    $wordId = getWordId($word);

    if ($userId !== null) {
        if (!checkCooldown($userId, $wordId)) {
            return;
        }
    }

    $sql = 'INSERT INTO views (user_id, word_id)
            VALUES (?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - logView Prepare failed: " . $mysqli->error);
        return;
    }

    $stmt->bind_param("ii", $userId, $wordId);
    $result = $stmt->execute();

    if (!$result) {
        error_log($userId . " - logView Execute failed: " . $stmt->error);
    }

    $stmt->close();
    return;
}

function checkCooldown(int $userId, int $wordId): bool
{
    $mysqli = getMysqli();

    $sql = 'SELECT viewed
            FROM views
            WHERE user_id = ? AND word_id = ?
            ORDER BY viewed DESC
            LIMIT 1';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - checkCooldown Prepare failed: " . $mysqli->error);
        return true;
    }
    $stmt->bind_param("ii", $userId, $wordId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return true;
    }

    $lastViewed = strtotime($row['viewed']);
    if ((time() - $lastViewed) >= 60) {
        return true;
    } else {
        return false;
    }
}
