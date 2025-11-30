<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../api/user.php';

sessionHandler();

$basePath = '/etydict/public/';
$template = 'login.html.twig';

$username_error = false;
$password_error = false;
$deactivated_error = false;
$username = '';

if ($_SESSION['user'] ?? false) {
    header('Location: ' . $basePath . 'account/profile/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['login']))) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user = getUserByUsername($username);
    if (!$user) {
        $username_error = true;
    } else {
        if (!verifyStr($password, $user['password'])) {
            $password_error = true;
        } else {
            if ($user['is_active'] == 0) {
                $deactivated_error = true;
            } else {
                userLogin($username, $password, $_POST['remember_me'] ?? 'off');
                header('Location: ' . $basePath . 'account/profile/');
                exit();
            }
        }
    }
}


$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'url' => $basePath,
    'username' => $username,
    'username_error' => $username_error,
    'password_error' => $password_error,
    'deactivated_error' => $deactivated_error,
]);
