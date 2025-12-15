<?php

declare(strict_types=1);

$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: '';
$recaptchaSecretKey = getenv('RECAPTCHA_SECRET_KEY') ?: '';

function getRecaptchaSiteKey(): string
{
    global $recaptchaSiteKey;
    return $recaptchaSiteKey;
}

function verifyRecaptcha(?string $response): bool
{
    global $recaptchaSecretKey;

    if (!$recaptchaSecretKey || !$response) {
        return false;
    }

    $payload = http_build_query([
        'secret' => $recaptchaSecretKey,
        'response' => $response,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Content-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => 5,
        ],
    ]);

    $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($verify === false) {
        return false;
    }

    $result = json_decode($verify, true);
    return isset($result['success']) && $result['success'] === true;
}
