<?php

require_once __DIR__ . '/../repo/user_repo.php';

function sessionHandler(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_COOKIE['auth'])) {
        $_SESSION['user_id'] = getUserIdByToken($_COOKIE['auth']);
        $_SESSION['user'] = getUserById((int)$_SESSION['user_id']);
    }
}

function userLogin(string $username, string $password, string $remember_me): void
{
    sessionHandler();
    $user = getUserByUsername($username);
    if ($user != null && verifyStr($password, $user['password'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user'] = $user;
        if ($remember_me !== 'off' && $remember_me !== '' && $remember_me !== '0') {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (60 * 60 * 24 * 30);
            setcookie('auth', $token, $expiry, "/", "", false, true);
            setToken((int)$user['id'], $token, $expiry);
        }
    } else {
        throw new Exception('Login failed.');
    }
}

function userLogout(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

function destroySession(): void
{
    session_unset();
    session_destroy();
}
