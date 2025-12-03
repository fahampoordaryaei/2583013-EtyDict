<?php

require_once __DIR__ . '/../config/db.php';

function getUserByUsername(string $username): ?array
{
    $mysqli = getMysqli();
    $sql = 'SELECT id, username, email, password, date_created, is_active
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
    $sql = 'SELECT id, username, email, password, date_created, is_active
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

function getUserIdByEmail(string $email): ?int
{
    $mysqli = getMysqli();
    $sql = 'SELECT id
            FROM users
            WHERE email = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($email . " - getUserIdByEmail Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {
        if (isset($row['id'])) {
            return (int) $row['id'];
        }
    }
    return null;
}

function createUser(array $user): bool
{
    $mysqli = getMysqli();

    $hash = encryptStr($user['password']);

    $sql = 'INSERT INTO users (username, email, password)
            VALUES (?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($user['username'] . " - createUser Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("sss", $user['username'], $user['email'], $hash);
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

function getFavoriteWords(int $userId): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT w.id, w.word, w.ipa
            FROM favorites AS f
            JOIN words AS w ON f.word_id = w.id
            WHERE f.user_id = ?
            ORDER BY f.id DESC';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - getFavoriteWords Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $forms = [];
        $wordId = (int) $row['id'];
        foreach (getForms($wordId) as $form) {
            $forms[] = $form['form'];
        }

        $favorites[] = [
            'word' => $row['word'],
            'ipa' => $row['ipa'],
            'forms' => formatForms($forms),
        ];
    }
    $stmt->close();
    return $favorites;
}

function getViewHistory(int $userId): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT w.id, w.word, w.ipa, v.viewed
            FROM views AS v
            JOIN words AS w ON v.word_id = w.id
            WHERE v.user_id = ?
            ORDER BY v.viewed DESC
            LIMIT 50';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - getViewHistory Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $forms = [];
        $wordId = (int) $row['id'];
        foreach (getForms($wordId) as $form) {
            $forms[] = $form['form'];
        }

        $history[] = [
            'word' => $row['word'],
            'ipa' => $row['ipa'],
            'forms' => formatForms($forms),
            'viewed' => $row['viewed'],
        ];
    }
    $stmt->close();
    return $history;
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

function editUsername(int $userId, string $newUsername): bool
{
    $mysqli = getMysqli();

    $sql = 'UPDATE users
            SET username = ?
            WHERE id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - editUsername Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("si", $newUsername, $userId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - editUsername Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function editEmail(int $userId, string $email): bool
{
    $mysqli = getMysqli();

    $sql = 'UPDATE users
            SET email = ?
            WHERE id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - editEmail Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("si", $email, $userId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - editEmail Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function editPassword(int $userId, string $password): bool
{
    $mysqli = getMysqli();

    $hash = encryptStr($password);

    $sql = 'UPDATE users
            SET password = ?
            WHERE id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - editPassword Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("si", $hash, $userId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - editPassword Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function deactivateUser(int $userId): bool
{
    $mysqli = getMysqli();

    $sql = 'UPDATE users
            SET is_active = 0
            WHERE id = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - deactivateUser Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - deactivateUser Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function generateResetToken(string $email): ?string
{
    $mysqli = getMysqli();

    $token = bin2hex(random_bytes(32));
    $hashed_token = hash('sha256', $token);
    $expiry = date('Y-m-d H:i:s', time() + (60 * 60));
    $userId = getUserIdByEmail($email);
    if ($userId === null) {
        return null;
    }

    $sql = 'INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("generateResetLink Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("iss", $userId, $hashed_token, $expiry);
    $success = $stmt->execute();

    if (!$success) {
        error_log("generateResetLink Execute failed: " . $stmt->error);
        return null;
    }

    $stmt->close();
    return $token;
}

function validateResetToken(string $token): ?int
{
    $mysqli = getMysqli();

    $sql = 'SELECT user_id
            FROM password_resets
            WHERE token_hash = ? AND expires_at > NOW()';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("validateToken Prepare failed: " . $mysqli->error);
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

function expireResetToken(string $token): bool
{
    $mysqli = getMysqli();

    $sql = 'UPDATE password_resets
            SET expires_at = NOW()
            WHERE token_hash = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("expireResetToken Prepare failed: " . $mysqli->error);
        return false;
    }

    $hashed_token = hash('sha256', $token);
    $stmt->bind_param("s", $hashed_token);
    $stmt->execute();
    if (!$stmt) {
        error_log("expireResetToken Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function etyWordIsFavorited(int $userId, string $word): ?bool
{
    $mysqli = getMysqli();

    $sql = 'SELECT COUNT(*) as count
            FROM ety_favorites
            WHERE user_id = ? AND word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - etyWordIsFavorited Prepare failed: " . $mysqli->error);
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

function favoriteEtyWord(int $userId, string $word): bool
{
    $mysqli = getMysqli();

    $sql = 'INSERT INTO ety_favorites (user_id, word)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE user_id = user_id';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - favoriteEtyWord Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("is", $userId, $word);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - favoriteEtyWord Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function unfavoriteEtyWord(int $userId, string $word): bool
{
    $mysqli = getMysqli();

    $sql = 'DELETE FROM ety_favorites
            WHERE user_id = ? AND word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - unfavoriteEtyWord Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("is", $userId, $word);
    $success = $stmt->execute();

    if (!$success) {
        error_log($userId . " - unfavoriteEtyWord Execute failed: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return true;
}

function getFavoriteEtyWords(int $userId): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT word
            FROM ety_favorites
            WHERE user_id = ?
            ORDER BY id DESC';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - getFavoriteEtyWords Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row['word'];
    }
    $stmt->close();
    return $favorites;
}

function getEtyViewHistory(int $userId): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT word, viewed
            FROM ety_views
            WHERE user_id = ?
            ORDER BY viewed DESC
            LIMIT 50';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($userId . " - getEtyViewHistory Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'word' => $row['word'],
            'viewed' => $row['viewed'],
        ];
    }
    $stmt->close();
    return $history;
}
