<?php

require_once __DIR__ . '/../config/db.php';

function getUserByUsername(string $username): ?array
{
    $mysqli = getMysqli();
    $sql = 'SELECT id, username, email, password, is_verified
            FROM users
            WHERE username = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($username . " - getUserByUsername Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    return $row;
}

function getUserById(int $userId): ?array
{
    $mysqli = getMysqli();
    $sql = 'SELECT id, username, email, password, is_verified
            FROM users
            WHERE id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - getUserById Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    return $row;
}

function createUser(array $user): bool
{
    $mysqli = getMysqli();

    $hash = encryptStr($user['password']);
    $is_verified = 0;

    $sql = 'INSERT INTO users (username, email, password, is_verified)
            VALUES (?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($user['username'] . " - createUser Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("sssi", $user['username'], $user['email'], $hash, $is_verified);
    $success = $stmt->execute();

    if (!$success) {
        error_log($user['username'] . " - createUser Execute failed: " . $stmt->error);
        return false;
    }

    $stmt->close();
    return true;
}

function encryptStr(string $str): string
{
    return password_hash($str, PASSWORD_BCRYPT);
}

function verifyStr(string $str, string $hash): bool
{
    return password_verify($str, $hash);
}

function setToken(int $userId, string $token, int $expiry): void
{
    $mysqli = getMysqli();

    $sql = 'INSERT INTO tokens (user_id, token, created, expires_at)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE token = VALUES(token), created = VALUES(created), expires_at = VALUES(expires_at)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - setToken Prepare failed: " . $mysqli->error);
    }

    $expiryDateTime = date('Y-m-d H:i:s', $expiry);
    $hashed_token = hash('sha256', $token);
    $stmt->bind_param("iss", $userId, $hashed_token, $expiryDateTime);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - setToken Execute failed: " . $stmt->error);
    }

    $stmt->close();
}

function getUserIdByToken(string $token): ?int
{
    $mysqli = getMysqli();

    $sql = 'SELECT user_id
            FROM tokens
            WHERE token = ? AND expires_at > NOW()';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("getUserIdByToken Prepare failed: " . $mysqli->error);
        return null;
    }

    $hashed_token = hash('sha256', $token);
    $stmt->bind_param("s", $hashed_token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && isset($row['user_id'])) {
        return (int) $row['user_id'];
    }
    return null;
}

function wordIsFavorited(int $userId, string $word): ?bool
{
    $mysqli = getMysqli();

    $sql = 'SELECT COUNT(*) as count
            FROM favorites AS f
            JOIN words AS w ON f.word_id = w.id
            WHERE f.user_id = ? AND w.word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - wordIsFavorited Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("is", $userId, $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return $row['count'] > 0;
    } else {
        return null;
    }
}

function favoriteWord(int $userId, int $wordId): bool
{
    $mysqli = getMysqli();

    $sql = 'INSERT INTO favorites (user_id, word_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE user_id = user_id';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - favoriteWord Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("ii", $userId, $wordId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - favoriteWord Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function unfavoriteWord(int $userId, int $wordId): bool
{
    $mysqli = getMysqli();

    $sql = 'DELETE FROM favorites
            WHERE user_id = ? AND word_id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - unfavoriteWord Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("ii", $userId, $wordId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - unfavoriteWord Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function sendUserMessage(int $userId, string $subject, string $message): bool
{
    $mysqli = getMysqli();

    $sql = 'INSERT INTO user_messages (user_id, subject, message)
            VALUES (?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - sendUserMessage Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("iss", $userId, $subject, $message);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - sendUserMessage Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}
