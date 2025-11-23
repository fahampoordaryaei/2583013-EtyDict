<?php
$word = isset($_GET['w']) ? trim($_GET['w']) : '';
if ($word === '') {
    http_response_code(400);
    echo 'No word provided';
    exit;
}

$word = mb_strtolower($word, 'UTF-8');

require_once '../../src/dict/dict_repo.php';

$wordData = getWord($word);

if (!$wordData) {
    http_response_code(404);
    echo 'Word not found';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($wordData);
exit;
