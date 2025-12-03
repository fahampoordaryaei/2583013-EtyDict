<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/json.php';

function searchHandler(): void
{
    header('Content-Type: application/json');
    $query = $_POST['query'];
    if (is_null($query) || $query === '') {
        giveJson([]);
        return;
    }
    $results = dictSearch($query);

    giveJson($results);
}

function feelingLucky(): string
{
    return randomWord();
}
