<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/repo/dict_repo.php';
require_once __DIR__ . '/../../src/api/ety.php';
require_once __DIR__ . '/../../src/lib/input_filter.php';
require_once __DIR__ . '/../../src/config/recaptcha.php';
require_once __DIR__ . '/../api/user.php';
require_once __DIR__ . '/../../src/log/viewlogger.php';

$basePath = '/';
$word = null;
$error = null;
$template = 'main/dictionary.html.twig';
$ety_available = false;
$is_favorite = false;
$suggestions = [];
$similarWords = [];
$popularWords = [];
$trendingWords = [];
$wotd = [];
$today = new DateTime();

sessionHandler();

$query = '';

if (isset($_GET['w'])) {
    $query = cleanText($_GET['w']) ?? '';
}

if ($query !== '') {
    $word = getWord($query);
    if (!$word) {
        $error = "No results for \"{$query}\" found.";
        $autocompleteWords = getAutocomplete($query, 100);
        if ($autocompleteWords) {
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
    } else {
        $ety_available = etyExists((string) $query);
        logView($_SESSION['user']['id'] ?? null, $word['word']);
    }
    $suggestions = wordSuggestions($query, 5);
    if (count($suggestions) === 0) {
        $suggestions = null;
    } else {
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
    }
} else {
    $wotd = GetWotd($today);
    $today = (string)$today->format('d / m / Y');
    $popularWords = getPopularWords();
    $trendingWords = getTrendingWords();
}


if (!empty($_SESSION['user'])) {
    $user = $_SESSION['user'];
    if ($word) {
        if (wordIsFavorited($_SESSION['user']['id'], $word['word'])) {
            $is_favorite = true;
        }
    }
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
    'suggestions' => $suggestions,
    'similar_words' => $similarWords,
    'popular_words' => $popularWords,
    'trending_words' => $trendingWords,
    'user' => $user ?? null,
    'wotd' => $wotd,
    'today' => $today,
    'recaptcha_error' => $recaptchaError,
    'csrf_token' => generateCsrfToken(),
]);
