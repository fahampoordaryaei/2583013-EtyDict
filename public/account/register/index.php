<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/lib/input_filter.php';
require_once __DIR__ . '/../../api/user.php';

sessionHandler();

$basePath = '/etydict/public/';
$template = 'register.html.twig';
$username = '';
$username_error = false;
$password_match_error = false;
$register_success = false;

if ($_SESSION['user'] ?? false) {
    header('Location: ' . $basePath . 'account/profile/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['register']))) {
    $username = cleanText($_POST['username'] ?? '');
    $email = cleanEmail($_POST['email'] ?? '');
    $password = trim((string) ($_POST['password'] ?? ''));
    $confirm_password = trim((string) ($_POST['confirm_password'] ?? ''));

    $invalidUser = false;
    if ($username === '' || preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username) !== 1) {
        $invalidUser = true;
    }
    if ($email === '') {
        $invalidUser = true;
    }
    if (mb_strlen($password) < 8) {
        $invalidUser = true;
    }
    if ($password !== $confirm_password) {
        $invalidUser = true;
        $password_match_error = true;
    }

    if ($invalidUser || checkUsername($username)) {
        $username_error = true;
    } else {
        userRegister($username, $email, $password);
        $register_success = true;
    }
}

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'url' => $basePath,
    'username_error' => $username_error,
    'password_match_error' => $password_match_error,
    'register_success' => $register_success
]);
