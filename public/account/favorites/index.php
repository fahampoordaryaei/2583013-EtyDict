<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/repo/user_repo.php';
require_once __DIR__ . '/../../api/user.php';
require_once __DIR__ . '/../../../src/api/ety.php';

$basePath = '/';

$mode = $_GET['mode'] ?? 'dictionary';

sessionHandler();

if (!($_SESSION['user'] ?? false)) {
    header('Location: ' . $basePath . 'account/login/');
    exit();
} else {
    $userId = (int) $_SESSION['user']['id'];
    $favorites = [];
    if ($mode === 'dictionary') {
        $favorites = getFavoriteWords($userId);
    } else {
        $favoriteWords = getFavoriteEtyWords($userId);
        foreach ($favoriteWords as $favoriteWord) {
            $favorites[] = ['word' => $favoriteWord];
        }
    }
    foreach ($favorites as $i => $favorite) {
        $favorites[$i]['is_favorite'] = true;
    }
}

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('account/favorites.html.twig', [
    'url' => $basePath,
    'user' => $_SESSION['user'],
    'favorites' => $favorites,
    'mode' => $mode,
    'csrf_token' => generateCsrfToken(),
]);
