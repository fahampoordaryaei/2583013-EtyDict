<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../lib/input_filter.php';
require_once __DIR__ . '/../api/user.php';

sessionHandler();

$basePath = '/';
$token = cleanToken($_GET['token'] ?? '');
$username = cleanText($_GET['username'] ?? '');
$template = 'email/verify-email.html.twig';

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'url' => $basePath,
    'token' => $token,
    'username' => $username,
]);