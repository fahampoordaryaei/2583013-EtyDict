<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/api/ety.php';
require_once __DIR__ . '/../../src/log/eventlogger.php';
require_once __DIR__ . '/../../src/lib/input_filter.php';

header('Content-Type: application/json');

$nodes = [];
$edges = [];

$word = cleanText($_GET['w'] ?? '');
if ($word === '') {
    logEvent('api_ety_missing_word', 400, 'Etymology API: missing word parameter');
    echo json_encode(['error' => 'Missing word']);
    exit;
}

$etyAC = etyAutocomplete($word);
if (count($etyAC) === 0) {
    http_response_code(404);
    logEvent('api_ety_not_found', 404, "Etymology API: no autocomplete matches for {$word}");
    exit;
}

$id = $etyAC[0]['_id'];

$treeUrl = 'https://api.etymologyexplorer.com/prod/get_trees?ids[]=' . urlencode((string) $id);
$etyTree = @file_get_contents($treeUrl);
$etyTree = json_decode($etyTree, true);

$words = $etyTree[1]['words'];
$relations = $etyTree[3];

foreach ($words as $w) {
    $definition = $w['entries'][0]['pos'][0]['definitions'][0] ?? null;
    $nodes[] = [
        'id' => $w['_id'],
        'word' => $w['word'],
        'language' => $w['language_name'] ?? '',
        'definition' => $definition,
    ];
}

foreach ($relations as $rel) {
    $edges[] = [
        'from' => $rel[0],
        'to' => $rel[1],
    ];
}

echo json_encode([
    'nodes' => $nodes,
    'edges' => $edges,
]);
