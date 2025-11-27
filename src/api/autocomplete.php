<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/dict_repo.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$mysqli = getMysqli();
$query = trim(mb_strtolower((string) ($_GET['query'] ?? '')));

$similarWords = getAutocomplete($query, 10);
foreach ($similarWords as &$similarWord) {
    foreach ($similarWord['forms'] as &$form) {
        switch ($form) {
            case 'noun':
                $form = 'n.';
                break;
            case 'verb':
                $form = 'v.';
                break;
            case 'adjective':
                $form = 'adj.';
                break;
            case 'adverb':
                $form = 'adv.';
                break;
            case 'preposition':
                $form = 'prep.';
                break;
            case 'interjection':
                $form = 'interj.';
                break;
            case 'pronoun':
                $form = 'pron.';
                break;
            case 'article':
                $form = 'art.';
                break;
            case 'phrase':
                $form = 'phr.';
                break;
            default:
                break;
        }
    }
    $similarWord['forms'] = implode(',', $similarWord['forms']);
}
echo json_encode($similarWords);
exit;
