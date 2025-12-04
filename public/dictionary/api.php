<?php
require_once __DIR__ . '/../../src/lib/input_filter.php';

if (isset($_GET['w'])) {
    $word = cleanText($_GET['w']);
} else {
    $word = '';
}

require_once __DIR__ . '/../../src/log/eventlogger.php';

if ($word === '') {
    http_response_code(400);
    logEvent('api_dictionary_missing_word', 400, 'Dictionary API: missing word parameter');
    echo 'No word provided';
    exit;
}

$word = mb_strtolower($word, 'UTF-8');

require_once '../../src/repo/dict_repo.php';

$wordData = getWord($word);

if (!$wordData) {
    http_response_code(404);
    logEvent('api_dictionary_word_not_found', 404, "Dictionary API: word not found ({$word})");
    $wordData = ['error' => ['Word not found']];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($wordData);
exit;
