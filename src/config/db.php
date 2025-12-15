<?php

declare(strict_types=1);

function getMysqli(): mysqli
{
    $db_host = getenv('DB_HOST') ?: '127.0.0.1';
    $db_name = getenv('DB_NAME') ?: 'dictionary';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_port = (int) (getenv('DB_PORT') ?: 3306);

    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($mysqli->connect_errno) {
        error_log('Database connection error: ' . $mysqli->connect_error);
        http_response_code(500);
        exit('Database connection failed.');
    }

    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
