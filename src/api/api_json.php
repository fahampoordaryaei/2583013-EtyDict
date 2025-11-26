<?php

function getEtyJson(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Accept: application/json",
            'timeout' => 10,
        ],
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (is_array($data)) {
        return $data;
    } else {
        return null;
    }
}
