<?php
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

require_once '../../src/repo/dict_repo.php';

$wordData = getWord($word);

if (!$wordData) {
    http_response_code(404);
    $wordData['error'] = ['Word not found'];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($wordData);
exit;
