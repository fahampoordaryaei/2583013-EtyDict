<?php

require_once __DIR__ . '/../config/db.php';

function getPopularEtyWords(): array
{
    $mysqli = getMysqli();
    $popularWords = [];

    $sql = 'SELECT ef.word, COUNT(ef.word) AS favorite_count
            FROM ety_favorites ef
            GROUP BY ef.word
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
        $popularWords[] = $row['word'];
    }

    $stmt->close();
    return $popularWords;
}

function getTrendingEtyWords(): array
{
    $mysqli = getMysqli();
    $trendingWords = [];

    $sql = 'SELECT ev.word, COUNT(ev.word) AS view_count
            FROM ety_views ev
            WHERE ev.viewed >= NOW() - INTERVAL 7 DAY
            GROUP BY ev.word
            ORDER BY view_count DESC
            LIMIT 5';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("getTrendingWords Prepare failed: " . $mysqli->error);
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trendingWords[] = $row['word'];
    }

    $stmt->close();
    return $trendingWords;
}

