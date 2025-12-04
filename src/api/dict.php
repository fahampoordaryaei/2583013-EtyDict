<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/json.php';
require_once __DIR__ . '/../lib/input_filter.php';

function sanitizeSearch(array $query): array
{
    $defaults = [
        'word' => '',
        'sort' => 'asc',
        'filter_fav' => false,
        'filter_viewed' => false,
        'form' => 'all',
        'dialect' => 'all',
        'label' => 'all',
    ];
    
    $word = cleanText($query['word'] ?? '');
    $sort = cleanText($query['sort'] ?? '');
    $form = strtolower(cleanText($query['form'] ?? ''));
    $form = preg_replace("/[^a-z\s-]/", '', $form) ?? '';
    $dialect = strtolower(cleanText($query['dialect'] ?? ''));
    $dialect = preg_replace("/[^a-z\s-]/", '', $dialect) ?? '';
    $label = strtolower(cleanText($query['label'] ?? ''));
    $label = preg_replace("/[^a-z\s-]/", '', $label) ?? '';

    return [
        'word' => $word,
        'sort' => $sort,
        'filter_fav' => cleanBool($query['filter_fav'] ?? false),
        'filter_viewed' => cleanBool($query['filter_viewed'] ?? false),
        'form' => $form === '' ? $defaults['form'] : $form,
        'dialect' => $dialect === '' ? $defaults['dialect'] : $dialect,
        'label' => $label === '' ? $defaults['label'] : $label,
    ];
}

function searchHandler(): void
{
    header('Content-Type: application/json');
    $query = $_POST['query'] ?? [];
    if (!is_array($query)) {
        giveJson([]);
        return;
    }
    $results = dictSearch(sanitizeSearch($query));

    giveJson($results);
}

function feelingLucky(): string
{
    return randomWord();
}
