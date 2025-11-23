<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/dict/dict_repo.php';
require_once __DIR__ . '/../../src/ety/ety_repo.php';

$basePath = '/etydict/public/';
$word = null;
if (isset($_GET['w'])) {
    $query = trim($_GET['w']) ?? '';
} else {
    $query = '';
}
$error = null;

if ($query === '') {
    http_response_code(400);
    $error = 'Please enter a word to search.';
    $template = 'dictionary.html.twig';
} else {
    $word = getWord($query);
    $error = '';
    $template = 'dictionary.html.twig';

    if (!$word) {
        http_response_code(404);
        $error = 'No entry found for ' . $query;
    }
}

// $ety_available = checkForEtymology($query);
// if ($ety_available) {
//     $ety_available = true;
// } else {
//     $ety_available = false;
// }
$ety_available = false;

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

header(header: 'Content-Type: text/html; charset=utf-8');

echo $twig->render($template, [
    'word' => $word,
    'query' => $query,
    'error' => $error,
    'url' => $basePath,
    'dict_url' => $basePath . 'dictionary/?w=',
    'ety_available' => $ety_available,
    // 'user' => $_SESSION['user']
]);
