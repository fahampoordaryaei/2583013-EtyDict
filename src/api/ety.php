<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/json.php';

const BASE_URL = 'https://api.etymologyexplorer.com/prod';

function etyExists(string $word, string $mode): bool
{
    $etyAC = etyAutocomplete($word);
    $id = $etyAC[0]['_id'] ?? 0;
    if ($id === 0) {
        return false;
    }

    if ($mode === 'etymology') {
        $hasEty = getTrees($id);

        if (count($hasEty) === 0) {
            return false;
        } else {
            return true;
        }
    } else {
        return true;
    }
}

function getTrees(int $id): array
{
    $treeUrl = 'https://api.etymologyexplorer.com/prod/get_trees?ids[]=' . urlencode((string) $id);
    $etyTree = @file_get_contents($treeUrl);
    $etyTree = json_decode($etyTree, true);

    return $etyTree[3];
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
