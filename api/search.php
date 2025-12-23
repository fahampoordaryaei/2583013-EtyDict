<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/api/dict.php';
require_once __DIR__ . '/../src/api/ety.php';
require_once __DIR__ . '/../src/api/json.php';
require_once __DIR__ . '/../src/log/eventlogger.php';

echo "DEBUG SEARCH AFTER REQUIRE"; exit;

function searchApiHandler(): void
require_once __DIR__ . '/../src/api/ety.php';
require_once __DIR__ . '/../src/api/json.php';
require_once __DIR__ . '/../src/log/eventlogger.php';

function searchApiHandler(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        searchHandler();
        return;
    }

    $action = $_GET['action'] ?? '';
    $mode = $_GET['mode'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'feelingLucky') {
        while ($word = feelingLucky()) {
            if ($word === '') {
                giveLogEvent('search_api_no_word_available', 500, 'No word available');
            }
            if (etyExists($word, $mode)) {
                break;
            }
        }
        giveJson(['word' => $word]);
        return;
    }

    giveLogEvent('search_api_unsupported_request', 400, 'Unsupported request');
}

if (!defined('SEARCH_API_EMBEDDED')) {
    searchApiHandler();
}
