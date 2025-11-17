<?php

function getWord(mysqli $mysqli, string $word): array
{
    $sql = "SELECT id, word, ipa, syllables, similar
            FROM words
            WHERE word = ?
            LIMIT 1";

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

function getMeanings(mysqli $mysqli, int $wordId): array
{
    $meanings = [];

    $sql = "SELECT id, definition, example, speech_part, priority, synonyms, antonyms
            FROM meanings
            WHERE word_id = ?
            ORDER BY priority ASC, id ASC";

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

        $meaningId = (int) $row['id'];
        $row['labels'] = getMeaningsLabels($mysqli, $meaningId);
        $meanings[] = $row;
    }
    $stmt->close();
    return $meanings;
}

function getMeaningsLabels(mysqli $mysqli, int $meaningId): array
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

function getWordLabels(mysqli $mysqli, int $wordId): array
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

function getParentLabel(mysqli $mysqli, int $parentId): array
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
