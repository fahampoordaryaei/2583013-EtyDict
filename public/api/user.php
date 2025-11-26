<?php

require_once __DIR__ . '/../../src/repo/user_repo.php';
require_once __DIR__ . '/../../src/controller/user_controller.php';

function handleUserRequest(): void
{
    session_start();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'session') {
            sessionHandler();
            if (!isset($_SESSION['user_id'])) {
                restoreSession();
            }
            return;
        } elseif ($action === 'login') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember_me = $_POST['remember_me'] ?? 'off';
            userLogin($username, $password, $remember_me);
        } elseif ($action === 'logout') {
            userLogout();
        }
    }
}

function restoreSession(): void
{
    if (isset($_COOKIE['auth'])) {
        $token = $_COOKIE['auth'];
        $userId = getUserIdByToken($token);
        if ($userId !== null) {
            $_SESSION['user_id'] = (int) $userId;
            $_SESSION['user'] = getUserById((int) $userId);
        } else {
            destroySession();
        }
    }
}

handleUserRequest();
