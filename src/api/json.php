<?php

function getJson(string $url, ?array $context = null): array
{
    $params = [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n"
    ];

    if (!empty($context)) {
        $params = array_merge($params, $context);
    }

    $params = [
        'http' => $params
    ];

    $response = file_get_contents($url, false, stream_context_create($params));

    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);

    if (is_array($data)) {
        return $data;
    } else {
        return [];
    }
}

function giveJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
