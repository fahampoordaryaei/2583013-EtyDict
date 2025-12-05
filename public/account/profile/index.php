<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../api/user.php';

$basePath = '/';

sessionHandler();

$email_success = $_SESSION['email_success'] ?? false;
unset($_SESSION['email_success']);

if (!isset($_SESSION['user'])) {
    header('Location: ' . $basePath . 'account/login/');
    exit();
}

$user = $_SESSION['user'];
$date_created = date('j F Y', strtotime($user['date_created']));

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('account/profile.html.twig', [
    'url' => $basePath,
    'user' => $user,
    'date_created' => $date_created,
    'email_success' => $email_success,
    'csrf_token' => generateCsrfToken(),
]);
