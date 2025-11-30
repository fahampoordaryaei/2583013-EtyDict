<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../api/user.php';

sessionHandler();

$basePath = '/etydict/public/';
$template = 'reset-password.html.twig';

$expired_token = false;
$show_reset_form = false;
$token = null;
$email = '';
$reset_link_sent = false;

if ($_SESSION['user'] ?? false) {
    header('Location: ' . $basePath . 'account/profile/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tokenParam = trim($_GET['token'] ?? '');
    if ($tokenParam !== '') {
        $validatedUser = validateResetToken($tokenParam);
        if ($validatedUser !== null) {
            $token = $tokenParam;
            $show_reset_form = true;
        } else {
            $expired_token = true;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetPassword'])) {
    $email = trim($_POST['email'] ?? '');
    $generatedToken = generateResetToken($email);
    if ($generatedToken !== null) {
        $token = $generatedToken;
        $reset_link_sent = true;
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
    'token' => $token,
    'expired_token' => $expired_token,
    'show_reset_form' => $show_reset_form,
    'reset_link_sent' => $reset_link_sent,
    'email' => $email,
]);
