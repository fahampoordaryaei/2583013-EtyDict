<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/config/recaptcha.php';
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../api/user.php';

sessionHandler();

$basePath = '/';
$template = 'account/login.html.twig';

$username_error = false;
$password_error = false;
$deactivated_error = false;
$recaptcha_error = false;
$username = '';
$changePass = $_GET['changePass'] ?? false;

if ($_SESSION['user'] ?? false) {
    header('Location: ' . $basePath . 'account/profile/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['login']))) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        header('Location: ' . $basePath . 'account/login/');
        exit();
    }

    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (!verifyRecaptcha($recaptchaResponse)) {
        header('Location: ' . $basePath . 'account/login/');
        exit();
    }

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
    'recaptcha_error' => $recaptcha_error,
    'changePass' => $changePass,
    'csrf_token' => generateCsrfToken(),
]);
