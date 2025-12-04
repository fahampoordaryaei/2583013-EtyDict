<?php

require_once __DIR__ . '/../api/json.php';

const BASE_URL = 'https://api.etymologyexplorer.com/prod';

function etyExists(string $word): bool
{
    $etyAC = etyAutocomplete($word);

    if (count($etyAC) === 0) {
        return false;
    } else {
        return true;
    }
}

function etyAutocomplete(string $word): array
{
    $word = trim($word);

    if ($word === '') {
        return [];
    }

    $query = http_build_query([
        'word' => $word,
        'language' => 'English',
    ]);

    $url = BASE_URL . '/autocomplete?' . $query;

    $json = getJson($url);

    if (empty($json)) {
        return [];
    }

    $autocompleteData = $json['auto_complete_data'] ?? [];

    if (!is_array($autocompleteData)) {
        return [];
    }

    $filtered = [];

    foreach ($autocompleteData as $autocompleteWord) {
        if (!is_array($autocompleteWord)) {
            continue;
        }

        if ($autocompleteWord['transliteration'] !== null) {
            continue;
        }
        $filtered[] = $autocompleteWord;
    }
    return $filtered;
}