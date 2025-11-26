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

function checkUsernameExists(string $username): bool
{
    return getUserByUsername($username) !== null;
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
