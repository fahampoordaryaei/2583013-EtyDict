<?php

require_once __DIR__ . '/../api/json.php';

const BASE_URL = 'https://api.etymologyexplorer.com/prod';

function getEtyWordId(string $word, string $language = 'English'): ?int
{
    $entries = etyAutocomplete($word, $language);

    if ($entries === null || $entries === []) {
        return null;
    }

    foreach ($entries as $entry) {
        if (isset($entry['_id'])) {
            return (int) $entry['_id'];
        }
    }

    return null;
}

function checkForEtymology(string $word): bool
{
    $wordId = getEtyWordId((string) $word);

    if ($wordId === null) {
        return false;
    } else {
        return true;
    }
}

function getTrees(int $wordId): ?array
{
    $url = BASE_URL . '/get_trees?ids[]=' . urlencode((string) $wordId);

    $json = getJson($url);

    if ($json === null) {
        return null;
    }

    return $json['tree'] ?? null;
}

function etyAutocomplete(string $query, string $language = 'English'): ?array
{
    $url = BASE_URL . '/autocomplete?word=' . urlencode($query) . '&language=' . urlencode($language);

    $json = getJson($url);

    if ($json === null) {
        return null;
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
