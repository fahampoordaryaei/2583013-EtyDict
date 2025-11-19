<?php

require_once 'db.php';

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

    $wordId = (int) $row['id'];
    $row['meanings'] = getMeanings($mysqli, $wordId);
    $row['labels'] = getWordLabels($mysqli, $wordId);

    if ($row['similar'] == null) {
        $row['similar'] = [];
    } else {
        $row['similar'] = explode('|', $row['similar']);
    }

    return $row;
}

function getMeanings($mysqli, int $wordId): array
{
    $meanings = [];

    $sql = 'SELECT m.id AS meaning_id, m.definition, m.example, m.priority, m.synonyms, m.antonyms,
            f.id AS form_id,
            f.name AS form_name
            FROM meanings m
            LEFT JOIN meanings_forms mf ON mf.meaning_id = m.id
            LEFT JOIN forms f ON f.id = mf.form_id
            WHERE m.word_id = ?
            ORDER BY f.name, m.priority';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log($wordId . " - getMeanings Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param("i", $wordId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['synonyms'] == null) {
            $row['synonyms'] = [];
        } else {
            $row['synonyms'] = explode('|', $row['synonyms']);
        }

        if ($row['antonyms'] == null) {
            $row['antonyms'] = [];
        } else {
            $row['antonyms'] = explode('|', $row['antonyms']);
        }

        $meaningId = (int) $row['meaning_id'];
        $row['labels'] = getMeaningsLabels($mysqli, $meaningId);
        $meanings[] = $row;
    }

    $meanings = GroupMeaningsByForm($meanings);

    $stmt->close();
    return $meanings;
}

function getMeaningsLabels($mysqli, int $meaningId): array
{
    $labels = [];

    $sql = 'SELECT lbl.id, lbl.parent, lbl.name, lbl.is_dialect
            FROM meanings_labels AS mlbl
            JOIN labels AS lbl ON mlbl.labels_id = lbl.id
            WHERE mlbl.meanings_id = ?
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
            $parentLabel = getParentLabel($mysqli, (int) $row['parent']);
            $row['parent_id'] = $row['parent'];
            $row['parent'] = $parentLabel;
        }
        $labels[] = $row;
    }

    $stmt->close();
    return $labels;
}

function GroupMeaningsByForm(array $meanings): array
{
    $grouped = [];

    foreach ($meanings as $meaning) {
        $formName = $meaning['form_name'];
        if (!isset($grouped[$formName])) {
            $grouped[$formName] = [];
        }
        unset($meaning['form_id'], $meaning['form_name']);
        $grouped[$formName][] = $meaning;
    }

    uasort($grouped, function (array $a, array $b): int {
        return count($b) <=> count($a);
    });

    return $grouped;
}

function getWordLabels($mysqli, int $wordId): array
{
    $labels = [];

    $sql = 'SELECT lbl.id, lbl.parent, lbl.name, lbl.is_dialect
            FROM words_labels AS wlbl
            JOIN labels AS lbl ON wlbl.labels_id = lbl.id
            WHERE wlbl.words_id = ?
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
            $parentLabel = getParentLabel($mysqli, (int) $row['parent']);
            $row['parent_id'] = $row['parent'];
            $row['parent'] = $parentLabel;
        }
        $labels[] = $row;
    }

    $stmt->close();
    return $labels;
}

function getParentLabel($mysqli, int $parentId): array
{
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
