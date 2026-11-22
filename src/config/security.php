<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

ini_set('display_errors', '0');
ini_set('expose_php', '0');

function setSecurityHeaders(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' https://cdn.jsdelivr.net; frame-src 'none'; connect-src 'self' https://cdn.jsdelivr.net; media-src 'self' blob:; frame-ancestors 'self'; form-action 'self';");
    header_remove('X-Powered-By');
}

function generateCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(mixed $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfToken(): void
{
    if (validateCsrfToken($_POST['csrf_token'] ?? null)) {
        return;
    }

    require_once __DIR__ . '/../api/json.php';
    giveJson(['error' => 'Invalid CSRF token'], 403);
}
