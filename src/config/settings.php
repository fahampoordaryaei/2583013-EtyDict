<?php

declare(strict_types=1);

function etydictSetting(string $key, string $default = ''): string
{
    $environment = getenv($key);
    if ($environment !== false) {
        return $environment;
    }

    static $settings;
    if ($settings === null) {
        $path = dirname(__DIR__, 2) . '/.env';
        $settings = is_file($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : [];
        if ($settings === false) {
            throw new RuntimeException('Unable to parse the EtyDict .env file.');
        }
    }

    return $settings[$key] ?? $default;
}

function etydictBaseUrl(): string
{
    return rtrim(etydictSetting('APP_URL', 'http://etydict.com'), '/') . '/';
}
