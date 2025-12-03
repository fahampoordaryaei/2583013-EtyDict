<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/json.php';

$mysqli = getMysqli();
$query = trim(mb_strtolower((string) ($_GET['query'] ?? '')));

if ($query === '') {
    giveJson([]);
    exit();
}

$similarWords = getAutocomplete($query, 5);
foreach ($similarWords as &$similarWord) {
    $similarWord['forms'] = formatForms($similarWord['forms']);
}

giveJson($similarWords);
exit();
