<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/user_repo.php';

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

function userRegister(string $username, string $email, string $password): void
{
    $user = [
        'username' => $username,
        'email' => $email,
        'password' => $password
    ];

    $success = createUser($user);
    if (!$success) {
        throw new Exception('User registration failed.');
    }
}

function submitUserMessage(int $userId, string $subject, string $message): bool
{
    return sendUserMessage($userId, $subject, $message);
}
