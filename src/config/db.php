<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

function getMysqli(): mysqli
{
    $mysqli = new mysqli(
        etydictSetting('DB_HOST', '127.0.0.1'),
        etydictSetting('DB_USERNAME'),
        etydictSetting('DB_PASSWORD'),
        etydictSetting('DB_DATABASE', 'dictionary'),
        (int) etydictSetting('DB_PORT', '3306'),
    );
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
