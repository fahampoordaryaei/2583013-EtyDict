<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/repo/user_repo.php';
require_once __DIR__ . '/../../api/user.php';
require_once __DIR__ . '/../../../src/api/ety.php';

$basePath = '/etydict/public/';

$mode = $_GET['mode'] ?? 'dictionary';

sessionHandler();

if (!($_SESSION['user'] ?? false)) {
    header('Location: ' . $basePath . 'account/login/');
    exit();
} else {
    $userId = (int) $_SESSION['user']['id'];
    if ($mode === 'dictionary') {
        $history = getViewHistory($userId);
    } elseif ($mode === 'etymology') {
        $history = getEtyViewHistory($userId);
    }
    $filteredHistory = [];
    $lastWord = null;
    for ($i = 0, $count = count($history); $i < $count; $i++) {
        $word = $history[$i];
        if ($lastWord === $word['word']) {
            continue;
        }
        $filteredHistory[] = $word;
        $lastWord = $word['word'];
    }
    $history = $filteredHistory;
}

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('history.html.twig', [
    'url' => $basePath,
    'user' => $_SESSION['user'],
    'history' => $history,
    'mode' => $mode
]);
