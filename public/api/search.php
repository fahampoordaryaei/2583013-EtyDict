<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/api/dict.php';
require_once __DIR__ . '/../../src/api/json.php';

function runSearchQuery(array $query): array
{
    return dictSearch($query);
}

function searchApiHandler(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        searchHandler();
        return;
    }

    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'feelingLucky') {
        $word = feelingLucky();
        if ($word === '') {
            giveJson(['error' => 'No word available'], 500);
        }
        giveJson(['word' => $word]);
        return;
    }

    giveJson(['error' => 'Unsupported request'], 400);
}

if (!defined('SEARCH_API_EMBEDDED')) {
    searchApiHandler();
}
