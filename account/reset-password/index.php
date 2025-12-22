<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/lib/input_filter.php';
require_once __DIR__ . '/../../src/config/recaptcha.php';
require_once __DIR__ . '/../../api/user.php';

sessionHandler();

$basePath = '/';
$template = 'account/reset-password.html.twig';

$expired_token = false;
$show_reset_form = false;
$valid_token = false;
$tokenParam = '';  // Initialize to prevent undefined variable warning

if ($_SESSION['user'] ?? false) {
    header('Location: ' . $basePath . 'account/profile/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    $tokenParam = cleanToken($token);
    if ($token !== '') {
        if ($tokenParam !== '' && validateCredToken($tokenParam)) {
            $valid_token = true;
            $show_reset_form = true;
        } else {
            $expired_token = true;
        }
    }
}

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'url' => $basePath,
    'valid_token' => $valid_token,
    'expired_token' => $expired_token,
    'show_reset_form' => $show_reset_form,
    'token' => $tokenParam,
    'csrf_token' => generateCsrfToken(),
]);
