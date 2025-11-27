<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/repo/dict_repo.php';
require_once __DIR__ . '/../../src/repo/ety_repo.php';
require_once __DIR__ . '/../api/user.php';
require_once __DIR__ . '/../../src/viewlogger.php';

$basePath = '/etydict/public/';
$word = null;
$error = null;
$template = 'dictionary.html.twig';
$ety_available = false;
$is_favorite = false;
$word_not_found = false;
$suggestions = null;
$similarWords = [];

sessionHandler();

if (isset($_GET['w'])) {
    $query = trim($_GET['w']) ?? '';
} else {
    $query = '';
}

if ($query !== '') {
    $word = getWord($query);
    if (!$word) {
        $error = "No results for \"{$query}\" found.";
        $word_not_found = true;
        $autocompleteWords = getAutocomplete($query, 100);
        if ($autocompleteWords) {
            $similarWords = [];
            foreach ($autocompleteWords as $autocompleteWord) {
                $resolvedWord = getWord($autocompleteWord['word']);
                $topForm = $resolvedWord['forms'][0];
                $topMeaning = $topForm['meanings'][0];

                $similarWords[] = [
                    'word' => $resolvedWord['word'],
                    'form_label' => $topForm['form'],
                    'definition' => $topMeaning['definition'],
                    'example' => $topMeaning['example']
                ];
            }
        }
    }
    $suggestions = wordSuggestions($query, 5);
    foreach ($suggestions as $i => $suggestion) {
        if ($suggestion === $query) {
            unset($suggestions[$i]);
        }
        foreach ($similarWords as $similarWord) {
            if ($suggestion === $similarWord['word']) {
                unset($suggestions[$i]);
            }
        }
    }
    if (count($suggestions) === 0) {
        $suggestions = null;
    }
    $ety_available = checkForEtymology((string) $query);
}

if (!empty($_SESSION['user'])) {
    $user = $_SESSION['user'];
    if ($word) {
        if (wordIsFavorited($_SESSION['user']['id'], $word['word'])) {
            $is_favorite = true;
        }
    }
}

if (is_array($word) && isset($word['word'])) {
    logView($_SESSION['user']['id'] ?? null, $word['word']);
}

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
    'is_favorite' => $is_favorite,
    'word_not_found' => $word_not_found,
    'suggestions' => $suggestions,
    'similar_words' => $similarWords,
    'user' => $user ?? null
]);
