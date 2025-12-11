<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function getWord(string $word): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT id, word, ipa, syllables, similar
            FROM words
            WHERE word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($word . " - getWord Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("s", $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [];
    }

    $wordId = $row['id'];
    $row['forms'] = getForms($wordId);
    foreach ($row['forms'] as &$form) {
        $formId = $form['form_id'];
        $form['meanings'] = getMeaningsByForm($wordId, $formId);
    }
    $row['labels'] = getWordLabels($wordId);

    if ($row['similar'] == null) {
        $row['similar'] = [];
    } else {
        $row['similar'] = explode('|', $row['similar']);
    }

    return $row;
}

function getForms(int $wordId): array
{
    $forms = [];
    $mysqli = getMysqli();

    $sql = 'SELECT wf.form_id, wf.priority, f.name AS form
            FROM words_forms as wf
            JOIN forms AS f on f.id = wf.form_id
            WHERE wf.word_id = ?
            ORDER BY wf.priority DESC;';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($wordId . " - getForms Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $wordId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }

    $stmt->close();
    return $forms;
}

function getMeaningsByForm(int $wordId, int $formId): array
{
    $meanings = [];
    $mysqli = getMysqli();

    $sql = 'SELECT m.id AS meaning_id, m.definition, m.example, m.priority, m.synonyms, m.antonyms
            FROM meanings m
            JOIN meanings_forms mf ON mf.meaning_id = m.id
            WHERE m.word_id = ? AND mf.form_id = ?
            ORDER BY m.priority DESC;';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($wordId . " - getMeaningsByForm Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("ii", $wordId, $formId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['synonyms'] == null) {
            $row['synonyms'] = [];
        } else {
            $row['synonyms'] = BuildSynAntList($row['synonyms'], 'synonym');
        }

        if ($row['antonyms'] == null) {
            $row['antonyms'] = [];
        } else {
            $row['antonyms'] = BuildSynAntList($row['antonyms'], 'antonym');
        }

        $meaningId = (int) $row['meaning_id'];
        $row['labels'] = getMeaningsLabels($mysqli, $meaningId);

        $meanings[] = $row;
    }
    $stmt->close();
    return $meanings;
}

function BuildSynAntList(string $words, string $type): array
{
    $array = [];
    $array = explode('|', $words);
    foreach ($array as &$word) {
        $word = trim($word);
        $word = [
            $type => $word,
            'exists' => dictExists($word)
        ];
    }
    return $array;
}

function dictExists(string $word): bool
{
    $mysqli = getMysqli();

    $sql = 'SELECT COUNT(*) as count
            FROM words
            WHERE word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($word . " - wordExists Prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("s", $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && $row['count'] > 0) {
        return true;
    } else {
        return false;
    }
}

function getMeaningsLabels($mysqli, int $meaningId): array
{
    $labels = [];

    $sql = 'SELECT lbl.id, lbl.parent, lbl.name, lbl.is_dialect
            FROM meanings_labels AS mlbl
            JOIN labels AS lbl ON mlbl.label_id = lbl.id
            WHERE mlbl.meaning_id = ?
            ORDER BY lbl.name ASC';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($meaningId . " - getMeaningsLabels Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('i', $meaningId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['parent'] !== null) {
            $parentLabel = getParentLabel((int) $row['parent']);
            $row['parent_id'] = $row['parent'];
            $row['parent'] = $parentLabel;
        }
        $labels[] = $row;
    }

    $stmt->close();
    return $labels;
}

function getWordLabels(int $wordId): array
{
    $labels = [];
    $mysqli = getMysqli();

    $sql = 'SELECT lbl.id, lbl.parent, lbl.name, lbl.is_dialect
            FROM words_labels AS wlbl
            JOIN labels AS lbl ON wlbl.label_id = lbl.id
            WHERE wlbl.word_id = ?
            ORDER BY lbl.name ASC';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($wordId . " - getWordLabels Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('i', $wordId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['parent'] !== null) {
            $parentLabel = getParentLabel((int) $row['parent']);
            $row['parent_id'] = $row['parent'];
            $row['parent'] = $parentLabel;
        }
        $labels[] = $row;
    }

    $stmt->close();
    return $labels;
}

function getParentLabel(int $parentId): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT id, name, parent, is_dialect
            FROM labels
            WHERE ID = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($parentId . " - getParentLabel Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return $row;
    } else {
        return [];
    }
}

function getAutocomplete(string $query, int $limit): array
{
    $autocompleteWords = [];
    $likeQuery = $query . '%';
    $mysqli = getMysqli();

    $sql = 'SELECT w.word
            FROM words AS w
            WHERE w.word LIKE ?
            ORDER BY w.word ASC
            LIMIT ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($query . " - getAutocomplete Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('si', $likeQuery, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $wordId = getWordId($row['word']);
        $forms = [];

        foreach (getForms($wordId) as $form) {
            $forms[] = $form['form'];
        }

        $autocompleteWords[] = [
            'word' => $row['word'],
            'forms' => $forms,
        ];
    }

    $stmt->close();
    return $autocompleteWords;
}

function getWordId(string $word): ?int
{
    $mysqli = getMysqli();

    $sql = 'SELECT id
            FROM words
            WHERE word = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($word . " - getWordIdByWord Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param('s', $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return $row['id'];
    } else {
        return null;
    }
}

function wordSuggestions(string $word, int $limit): array
{
    $mysqli = getMysqli();
    $word = trim($word);
    if ($word === '') {
        return [];
    }

    $query = '%';
    $length = strlen($word);
    for ($i = 0; $i < $length; $i += 2) {
        $query = $query . substr($word, $i, 2) . '%';
    }

    $sql = 'SELECT word
            FROM words
            WHERE word LIKE ?
            ORDER BY CHAR_LENGTH(word) ASC
            LIMIT ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($word . " - wordSuggestions Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('si', $query, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];

    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['word'];
    }

    $stmt->close();
    return $suggestions;
}

function dictSearch(array $query): array
{
    $mysqli = getMysqli();

    $word = $query['word'] ?? '*';
    $sort = strtoupper($query['sort'] ?? 'ASC');
    $filterFav = !empty($query['filter_fav']);
    $filterViewed = !empty($query['filter_viewed']);
    $form = $query['form'] ?? '*';
    $dialect = $query['dialect'] ?? '*';
    $label = $query['label'] ?? '*';
    $userId = isset($query['user_id']) ? (int) $query['user_id'] : null;

    if ($sort !== 'DESC') {
        $sort = 'ASC';
    }

    $sql = 'SELECT DISTINCT w.id, w.word, w.ipa, w.syllables,
                m.definition AS definition,
                m.example AS example
            FROM words w
            LEFT JOIN meanings m ON m.word_id = w.id
            LEFT JOIN words_forms wf ON wf.word_id = w.id
            LEFT JOIN forms wf_form ON wf_form.id = wf.form_id
            LEFT JOIN meanings_forms mf ON mf.meaning_id = m.id
            LEFT JOIN forms mf_form ON mf_form.id = mf.form_id
            LEFT JOIN words_labels wl ON wl.word_id = w.id
            LEFT JOIN labels wl_label ON wl_label.id = wl.label_id
            LEFT JOIN meanings_labels ml ON ml.meaning_id = m.id
            LEFT JOIN labels ml_label ON ml_label.id = ml.label_id
            LEFT JOIN favorites fav ON fav.word_id = w.id
            LEFT JOIN views vw ON vw.word_id = w.id';

    $conditions = ['1=1'];
    $types = '';
    $params = [];

    if ($word !== '*' && $word !== '') {
        $conditions[] = 'w.word LIKE ?';
        $types .= 's';
        $params[] = $word . '%';
    }

    if ($form !== '*' && strtolower($form) !== 'all') {
        $conditions[] = '(wf_form.name = ? OR mf_form.name = ?)';
        $types .= 'ss';
        $params[] = $form;
        $params[] = $form;
    }

    if ($label !== '*' && strtolower($label) !== 'all') {
        $conditions[] = '((wl_label.is_dialect = 0 AND wl_label.name = ?) OR (ml_label.is_dialect = 0 AND ml_label.name = ?))';
        $types .= 'ss';
        $params[] = $label;
        $params[] = $label;
    }

    if ($dialect !== '*' && strtolower($dialect) !== 'all') {
        $conditions[] = '((wl_label.is_dialect = 1 AND wl_label.name = ?) OR (ml_label.is_dialect = 1 AND ml_label.name = ?))';
        $types .= 'ss';
        $params[] = $dialect;
        $params[] = $dialect;
    }

    if ($filterFav && $userId) {
        $conditions[] = 'fav.user_id = ?';
        $types .= 'i';
        $params[] = $userId;
    }

    if ($filterViewed && $userId) {
        $conditions[] = 'vw.user_id = ?';
        $types .= 'i';
        $params[] = $userId;
    }

    $sql .= ' WHERE ' . implode(' AND ', $conditions) .
        ' GROUP BY w.id, w.word, w.ipa, w.syllables'
        . ' ORDER BY w.word ' . $sort
        . ' LIMIT 50';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('dictSearch prepare failed: ' . $mysqli->error);
        return [];
    }

    if ($types !== '') {
        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        $stmt->bind_param(...$bindParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = [
            'id' => (int) $row['id'],
            'word' => $row['word'],
            'ipa' => $row['ipa'],
            'syllables' => $row['syllables'],
            'definition' => $row['definition'],
            'example' => $row['example'],
        ];
    }

    $stmt->close();
    return $entries;
};

function randomWord(): string
{
    $mysqli = getMysqli();

    $sql = 'SELECT word 
            FROM words
            WHERE word NOT LIKE "% %"
            ORDER BY RAND()
            LIMIT 1';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("randomWord Prepare failed: " . $mysqli->error);
        return '';
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $word = '';
    if ($row = $result->fetch_assoc()) {
        $word = $row['word'];
    }
    $stmt->close();
    return $word;
}

function GetWotd(DateTime $date): array
{
    $mysqli = getMysqli();

    $dateStr = $date->format('Y-m-d');
    $date = (int) str_replace('-', '', $dateStr);

    $sql = 'SELECT w.word
            FROM wotd
            JOIN words AS w ON wotd.word_id = w.id
            WHERE wotd.date = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("GetWotd Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('i', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return getWord($row['word']);
    } else {
        return [];
    }
}

function getTrendingWords(): array
{
    $mysqli = getMysqli();

    $sql = 'SELECT w.word, COUNT(v.word_id) AS view_count
            FROM views v
            JOIN words w ON v.word_id = w.id
            WHERE v.viewed >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY w.id, w.word
            ORDER BY view_count DESC
            LIMIT 5';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("getTrendingWords Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $trendingWords = [];
    while ($row = $result->fetch_assoc()) {
        $trendingWords[] = $row['word'];
    }

    $stmt->close();
    return $trendingWords;
}

function getPopularWords(): array
{
    $mysqli = getMysqli();
    $popularWords = [];

    $sql = 'SELECT w.word, COUNT(f.word_id) AS favorite_count
            FROM favorites f
            JOIN words w ON f.word_id = w.id
            GROUP BY w.id, w.word
            ORDER BY favorite_count DESC
            LIMIT 5';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("getPopularWords Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['word']) {
            $popularWords[] = $row['word'];
        }
    }

    $stmt->close();
    return $popularWords;
}

function formatForms(array $forms): string
{
    foreach ($forms as &$form) {
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
    return implode(' / ', $forms);
}
