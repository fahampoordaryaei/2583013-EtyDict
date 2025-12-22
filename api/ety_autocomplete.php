<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/api/ety.php';

$query = trim((string) ($_GET['query'] ?? ''));

if ($query !== '') {
    $suggestions = array_slice(etyAutocomplete($query), 0, 5);
    giveJson($suggestions);
} else {
    giveJson([]);
}

exit();
