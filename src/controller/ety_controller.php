<?php

require_once __DIR__ . '/../repo/ety_repo.php';

if (isset($_GET['w'])) {
    $word = trim($_GET['w']);
} else {
    $word = '';
}

if ($word === '') {
    http_response_code(400);
    echo 'No word provided';
    exit;
}

$word = mb_strtolower($word, 'UTF-8');
$wordData = etyAutocomplete($word);

if (!$wordData) {
    http_response_code(404);
    echo 'Word not found';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($wordData);
exit;
