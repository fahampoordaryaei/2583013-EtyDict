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
    $row['forms'] = getFormsByWord($mysqli, $wordId);
    $row['labels'] = getWordLabels($mysqli, $wordId);

    if ($row['similar'] == null) {
        $row['similar'] = [];
    } else {
        $row['similar'] = explode('|', $row['similar']);
    }

    return $row;
}

function getFormsByWord(mysqli $mysqli, string $wordId): array
{
    $forms = [];

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

    foreach ($forms as &$form) {
        $formId = $form['form_id'];
        $form['meanings'] = getMeaningsByForm($mysqli, $wordId, $formId);
    }

    $stmt->close();
    return $forms;
}

function getMeaningsByForm(mysqli $mysqli, int $wordId, int $formId): array
{
    $meanings = [];

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
    $stmt->close();
    return $meanings;
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
            $parentLabel = getParentLabel($mysqli, (int) $row['parent']);
            $row['parent_id'] = $row['parent'];
            $row['parent'] = $parentLabel;
        }
        $labels[] = $row;
    }

    $stmt->close();
    return $labels;
}

function getWordLabels($mysqli, int $wordId): array
{
    $labels = [];

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