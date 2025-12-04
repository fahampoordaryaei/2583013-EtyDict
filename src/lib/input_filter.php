<?php

declare(strict_types=1);

function cleanText(string $value, int $maxLength = 255): string
{
    $value = trim($value);
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $value);
    return mb_substr($value, 0, $maxLength);
}

function cleanEmail(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $filtered = filter_var($value, FILTER_VALIDATE_EMAIL);
    if ($filtered === false) {
        return '';
    }
    return $filtered;
}

function cleanToken(string $value): string
{
    $value = trim($value);
    if (preg_match('/^[a-f0-9]{32,64}$/i', $value)) {
        return $value;
    }
    return '';
}

function cleanBool(mixed $value): bool
{
    $result = filter_var($value, FILTER_VALIDATE_BOOL);
    if ($result === null) {
        return false;
    }
    return $result;
}
