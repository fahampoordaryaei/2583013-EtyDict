<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/api/dict.php';
require_once __DIR__ . '/../../src/api/json.php';
require_once __DIR__ . '/../../src/log/eventlogger.php';

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
            giveLogEvent('search_api_no_word_available', 500, 'No word available');
        }
        giveJson(['word' => $word]);
        return;
    }

    giveLogEvent('search_api_unsupported_request', 400, 'Unsupported request');
}

if (!defined('SEARCH_API_EMBEDDED')) {
    searchApiHandler();
}
