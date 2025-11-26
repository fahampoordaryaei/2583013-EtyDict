<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/repo/dict_repo.php';
require_once __DIR__ . '/../../src/repo/ety_repo.php';
require_once __DIR__ . '/../../src/controller/user_controller.php';

$basePath = '/etydict/public/';
$word = null;

if (isset($_GET['w'])) {
    $query = trim($_GET['w']) ?? '';
} else {
    $query = '';
}
$error = null;
$template = 'dictionary.html.twig';
$ety_available = false;

if ($query === '') {
} else {
    $word = getWord($query);
    $ety_available = checkForEtymology((string) $query);
}

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

sessionHandler();
header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'word' => $word,
    'query' => $query,
    'error' => $error,
    'url' => $basePath,
    'dict_url' => $basePath . 'dictionary/?w=',
    'ety_available' => $ety_available,
    'user' => $_SESSION['user'] ?? null
]);
