<?php

declare(strict_types=1);

require_once __DIR__ . '/../controller/user_controller.php';
require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/../api/json.php';

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
        $token = $_COOKIE['auth'];
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
        session_start();
    }
    restoreSession();
}

function userLogout(): void
{
    sessionHandler();
    if (session_status() === PHP_SESSION_ACTIVE) {
        destroySession();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }
    setcookie('auth', '', time() - 3600, '/', '', false, true);
}

function checkUsername(string $username): bool
{
    $user = getUserByUsername($username);
    return $user !== null;
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



function apiHandler(): void
{
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        giveJson(['error' => 'Only POST supported'], 405);
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === '') {
        giveJson(['error' => 'Unknown action'], 400);
    }

    $tokenParam = trim((string) ($_POST['token'] ?? ''));
    $isTokenReset = $action === 'resetPassword' && $tokenParam !== '';
    $userId = null;
    if ($isTokenReset) {
        $validatedUser = validateResetToken($tokenParam);
        if ($validatedUser === null) {
            giveJson(['error' => 'Invalid or expired reset token'], 400);
        }
        $userId = $validatedUser;
    } else {
        if (!checkAuth()) {
            giveJson(['error' => 'Not authenticated'], 401);
        }
        sessionHandler();
        $user = $_SESSION['user'];
        $userId = (int) $user['id'];
    }

    switch ($action) {
        case 'toggleFavorite':
            $word = trim((string) ($_POST['word']));

            $result = toggleFavoriteWord($userId, $word);
            if (!$result['success']) {
                giveJson(['error' => 'Unable to update favorites'], 500);
            }
            giveJson(['success' => true, 'favorited' => $result['favorited']], 200);
            break;
        case 'editUsername':
            $username = trim((string) ($_POST['editUsername']));
            $success = editUsername($userId, $username);
            if (!$success) {
                giveJson(['error' => 'Unable to update username'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        case 'editEmail':
            $email = trim((string) ($_POST['editEmail']));
            $success = editEmail($userId, $email);
            if (!$success) {
                giveJson(['error' => 'Unable to update email'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        case 'changePassword':
            $password = trim((string) ($_POST['changePassword']));
            $success = editPassword($userId, $password);
            if (!$success) {
                giveJson(['error' => 'Unable to update password'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        case 'deactivateUser':
            $success = deactivateUser($userId);
            if (!$success) {
                giveJson(['error' => 'Unable to deactivate account'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        case 'sendMessage':
            $subject = trim((string) ($_POST['subject']));
            $message = trim((string) ($_POST['message']));
            $success = submitUserMessage($userId, $subject, $message);
            if (!$success) {
                giveJson(['error' => 'Unable to send message'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        case 'resetPassword':
            $password = trim((string) ($_POST['Password']));
            if ($isTokenReset) {
                expireResetToken(token: $tokenParam);
            }
            $success = editPassword($userId, $password);
            if (!$success) {
                giveJson(['error' => 'Unable to update password'], 500);
            }
            giveJson(['success' => true], 200);
            break;
        default:
            giveJson(['error' => 'Unknown action'], 400);
    }
}

sessionHandler();
