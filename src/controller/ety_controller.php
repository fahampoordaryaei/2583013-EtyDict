<?php

require_once __DIR__ . '/../repo/ety_repo.php';
require_once __DIR__ . '/../log/eventlogger.php';
require_once __DIR__ . '/../lib/input_filter.php';

if (isset($_GET['w'])) {
    $word = cleanText($_GET['w']);
} else {
    $word = '';
}

if ($word === '') {
    http_response_code(400);
    logEvent('ety_controller_missing_word', 400, 'Etymology controller: no word parameter provided');
    echo 'No word provided';
    exit;
}

$word = mb_strtolower($word, 'UTF-8');
$wordData = etyAutocomplete($word);

if (!$wordData) {
    http_response_code(404);
    logEvent('ety_controller_word_not_found', 404, "Etymology controller: word not found ({$word})");
    echo 'Word not found';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($wordData);
exit;
