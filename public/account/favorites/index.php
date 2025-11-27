<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../api/user.php';

$basePath = '/etydict/public/';

sessionHandler();

if (!($_SESSION['user'] ?? false)) {
    header('Location: ' . $basePath . 'account/login/');
    exit();
}

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('favorites.html.twig', [
    'url' => $basePath,
    'user' => $_SESSION['user'],
]);
