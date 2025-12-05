<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/repo/dict_repo.php';
require_once __DIR__ . '/../../src/repo/ety_repo.php';
require_once __DIR__ . '/../../src/api/ety.php';
require_once __DIR__ . '/../../src/lib/input_filter.php';
require_once __DIR__ . '/../../src/config/recaptcha.php';
require_once __DIR__ . '/../api/user.php';
require_once __DIR__ . '/../../src/log/viewlogger.php';

$basePath = '/';
$error = null;
$template = 'etymology.html.twig';
$dict_available = false;
$is_favorite = false;
$suggestions = [];
$popularWords = [];
$trendingWords = [];
$hasEty = false;
$renderEty = false;
$user =	null;
$recaptchaError = false;

sessionHandler();

$query = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['w'])) {
	$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
	if (!verifyRecaptcha($recaptchaResponse)) {
		$recaptchaError = true;
	} else {
		$query = cleanText($_POST['w']) ?? '';
	}
} elseif (isset($_GET['w'])) {
	$query = cleanText($_GET['w']) ?? '';
}

if (!empty($_SESSION['user'])) {
	$user = $_SESSION['user'];
}

if ($query !== '') {
	$hasEty = etyExists($query);
	if ($hasEty) {
		$renderEty = true;
		$dict_available = dictExists($query);
		logEtyView($_SESSION['user']['id'] ?? null, $query);
		if ($user) {
			if (etyWordIsFavorited($_SESSION['user']['id'], $query)) {
				$is_favorite = true;
			}
		}
	} else {
		$error = "No etymology for \"{$query}\" found.";
		$suggestions = etyAutocomplete($query);
		if (count($suggestions) === 0) {
			$suggestions = null;
		} else {
			$suggestions = array_slice($suggestions, 0, 5);
		}
	}
	$dict_available = dictExists($query);
}

$popularWords = getPopularEtyWords();
$trendingWords = getTrendingEtyWords();

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
	'cache' => false,
	'autoescape' => 'html',
]);

echo $twig->render('main/etymology.html.twig', [
	'url' => $basePath,
	'user' => $user,
	'hasEty' => $hasEty,
	'error' => $error,
	'suggestions' => $suggestions,
	'popular_words' => $popularWords,
	'trending_words' => $trendingWords,
	'word' => $query,
	'is_favorite' => $is_favorite,
	'render_ety' => $renderEty,
	'dict_available' => $dict_available,
	'csrf_token' => generateCsrfToken(),
]);
