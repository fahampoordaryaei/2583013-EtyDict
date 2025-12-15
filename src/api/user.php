<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../controller/user_controller.php';
require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/../api/json.php';
require_once __DIR__ . '/../log/eventlogger.php';
require_once __DIR__ . '/../lib/input_filter.php';

function restoreSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $userId = (int) $user['id'];
        $_SESSION['user'] = getUserById($userId);
    } elseif (isset($_COOKIE['auth'])) {
        $token = cleanToken($_COOKIE['auth']);
        if ($token === '') {
            userLogout();
            return;
        }
        $userId = getUserIdByToken($token);
        if ($userId !== null) {
            $_SESSION['user'] = getUserById((int) $userId);
        } else {
            userLogout();
        }
    }
}

function sessionHandler(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }

    setSecurityHeaders();
    restoreSession();
}

function userLogout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        destroySession();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            $params['httponly'] ?? true
        );
    }
    setcookie('auth', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
}

function checkUsername(string $username): bool
{
    $user = getUserByUsername($username);
    return $user !== null;
}

function checkEmail(string $email): bool
{
    $userId = getUserIdByEmail($email);
    return $userId !== null;
}

function destroySession(): void
{
    session_unset();
    session_destroy();
}

function checkAuth(): bool
{
    sessionHandler();
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function toggleFavoriteWord(int $userId, string $word): array
{
    $wordId = getWordId($word);
    if ($wordId === null) {
        return ['success' => false, 'favorited' => false];
    }

    $favorited = wordIsFavorited($userId, $word) ?? false;

    if ($favorited) {
        $success = unfavoriteWord($userId, $wordId);
        if ($success) {
            $favorited = false;
        }
    } else {
        $success = favoriteWord($userId, $wordId);
        if ($success) {
            $favorited = true;
        }
    }

    return ['success' => $success ?? false, 'favorited' => $favorited];
}

function toggleEtyFavoriteWord(int $userId, string $word): array
{
    $word = trim($word);
    if ($word === '') {
        return ['success' => false, 'favorited' => false];
    }

    $favorited = etyWordIsFavorited($userId, $word) ?? false;

    if ($favorited) {
        $success = unfavoriteEtyWord($userId, $word);
        if ($success) {
            $favorited = false;
        }
    } else {
        $success = favoriteEtyWord($userId, $word);
        if ($success) {
            $favorited = true;
        }
    }

    return ['success' => $success ?? false, 'favorited' => $favorited];
}

function apiHandler(): void
{
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        giveLogEvent('user_api_method_not_allowed', 405, 'Only POST supported');
    }

    requireCsrfToken();

    $action = cleanText($_POST['action'] ?? '', 40);
    if ($action === '') {
        giveLogEvent('user_api_unknown_action', 400, 'Unknown action');
    }

    $tokenParam = cleanToken($_POST['token'] ?? '');
    if ($action === 'resetPassword' && $tokenParam !== '') {
        $isTokenReset = true;
    } else {
        $isTokenReset = false;
    }

    $userId = null;

    if ($isTokenReset) {
        $validatedToken = validateCredToken($tokenParam);
        if ($validatedToken === null) {
            giveLogEvent('user_api_invalid_reset_token', 400, 'Invalid or expired reset token');
        }
        $userId = $validatedToken['user_id'];
    } else {
        if (!checkAuth()) {
            giveLogEvent('user_api_not_authenticated', 401, 'Not authenticated');
        }
        sessionHandler();
        $user = $_SESSION['user'];
        $userId = (int) $user['id'];
    }

    switch ($action) {
        case 'toggleFavorite':
            $word = cleanText($_POST['word'] ?? '');
            if ($word === '') {
                giveLogEvent('user_api_invalid_word', 400, 'Invalid word provided');
            }
            $result = toggleFavoriteWord($userId, $word);
            if (!$result['success']) {
                giveLogEvent('user_api_toggle_favorite_failed', 500, 'Unable to update favorites');
            }
            giveJson(['success' => true, 'favorited' => $result['favorited']], 200);
            break;

        case 'toggleEtyFavorite':
            $word = cleanText($_POST['word'] ?? '');
            if ($word === '') {
                giveLogEvent('user_api_invalid_word', 400, 'Invalid word provided');
            }
            $result = toggleEtyFavoriteWord($userId, $word);
            if (!$result['success']) {
                giveLogEvent('user_api_toggle_ety_favorite_failed', 500, 'Unable to update favorites');
            }
            giveJson(['success' => true, 'favorited' => $result['favorited']], 200);
            break;

        case 'editUsername':
            $username = cleanText($_POST['editUsername'] ?? '', 30);
            if ($username === '' || preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username) !== 1) {
                giveLogEvent('user_api_update_username_failed', 400, 'Invalid username');
            }
            $success = editUsername($userId, $username);
            if (!$success) {
                giveLogEvent('user_api_update_username_failed', 500, 'Unable to update username');
            }
            giveJson(['success' => true], 200);
            break;

        case 'changePassword':
            $password = trim((string) ($_POST['changePassword'] ?? ''));
            if (mb_strlen($password) < 8) {
                giveLogEvent('user_api_change_password_failed', 400, 'Password must be at least 8 characters');
            }
            $success = editPassword($userId, $password);
            if (!$success) {
                giveLogEvent('user_api_change_password_failed', 500, 'Unable to update password');
            }
            giveJson(['success' => true], 200);
            break;

        case 'deactivateUser':
            $success = deactivateUser($userId);
            if (!$success) {
                giveLogEvent('user_api_deactivate_user_failed', 500, 'Unable to deactivate account');
            }
            giveJson(['success' => true], 200);
            break;

        case 'sendMessage':
            $subject = cleanText($_POST['subject'] ?? '', 120);
            $message = cleanText($_POST['message'] ?? '', 1000);
            if ($subject === '' || $message === '') {
                giveLogEvent('user_api_message_too_short', 400, 'Subject and message are required');
            }
            if (mb_strlen($message) > 1000) {
                giveLogEvent('user_api_message_too_long', 406, 'Message must be 1000 characters or fewer');
            }
            $success = submitUserMessage($userId, $subject, $message);
            if (!$success) {
                giveLogEvent('user_api_send_message_failed', 500, 'Unable to send message');
            }
            giveJson(['success' => true], 200);
            break;

        case 'resetPassword':
            $password = trim((string) ($_POST['Password'] ?? ''));
            if (mb_strlen($password) < 8) {
                giveLogEvent('user_api_reset_password_failed', 400, 'Password must be at least 8 characters');
            }
            if ($isTokenReset) {
                expireCredToken(token: $tokenParam);
            }
            $success = editPassword($userId, $password);
            if (!$success) {
                giveLogEvent('user_api_reset_password_failed', 500, 'Unable to update password');
            }
            giveJson(['success' => true], 200);
            break;

        default:
            giveLogEvent('user_api_unknown_action', 400, 'Unknown action');
    }
}

sessionHandler();
