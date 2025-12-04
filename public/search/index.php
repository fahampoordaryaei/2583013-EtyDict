<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/lib/input_filter.php';
require_once __DIR__ . '/../api/user.php';

if (!defined('SEARCH_API_EMBEDDED')) {
	define('SEARCH_API_EMBEDDED', true);
}

require_once __DIR__ . '/../api/search.php';

$basePath = '/etydict/public/';
$queryDefaults = [
	'word' => '',
	'sort' => 'asc',
	'filter_fav' => false,
	'filter_viewed' => false,
	'form' => 'all',
	'dialect' => 'all',
	'label' => 'all',
];

sessionHandler();

$query = $queryDefaults;
$results = [];
$hasResults = false;
$noResults = false;
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
	$query['word'] = cleanText($_GET['word'] ?? '');
	$query['sort'] = cleanText($_GET['sort'] ?? '');
	$query['filter_fav'] = cleanBool($_GET['filter_fav'] ?? false);
	$query['filter_viewed'] = cleanBool($_GET['filter_viewed'] ?? false);

	$form = strtolower(cleanText($_GET['form'] ?? ''));
	$form = preg_replace("/[^a-z\s-]/", '', $form) ?? '';
	$query['form'] = $form === '' ? $queryDefaults['form'] : $form;

	$dialect = strtolower(cleanText($_GET['dialect'] ?? ''));
	$dialect = preg_replace("/[^a-z\s-]/", '', $dialect) ?? '';
	$query['dialect'] = $dialect === '' ? $queryDefaults['dialect'] : $dialect;

	$label = strtolower(cleanText($_GET['label'] ?? ''));
	$label = preg_replace("/[^a-z\s-]/", '', $label) ?? '';
	$query['label'] = $label === '' ? $queryDefaults['label'] : $label;

	if ($userId !== null) {
		$query['user_id'] = $userId;
	}

	$rawResults = runSearchQuery($query);
	$hasResults = true;

	if (empty($rawResults)) {
		$noResults = true;
	} else {
		foreach ($rawResults as $result) {
			$results[] = [
				'word' => $result['word'],
				'definition' => $result['definition'] ?? null,
				'example' => $result['example'] ?? null,
			];
		}
	}
}

$loader = new FilesystemLoader(__DIR__ . '/../../templates');
$twig = new Environment($loader, [
	'cache' => false,
	'autoescape' => 'html',
]);

header('Content-Type: text/html; charset=utf-8');

echo $twig->render('search.html.twig', [
	'url' => $basePath,
	'user' => $user,
	'query' => $query,
	'has_results' => $hasResults,
	'no_results' => $noResults,
	'results' => $results,
]);
