<?php

declare(strict_types=1);

require_once __DIR__ . '/../log/eventlogger.php';

function getMysqli(): mysqli
{
    $db_host = 'localhost';
    $db_name = 'dictionary';
    $db_user = 'root';
    $db_pass = '';
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($mysqli->connect_error) {
        giveLogEvent('db_connection_error', 500, 'Database connection error: ' . $mysqli->connect_error);
        exit();
    }

    return $mysqli;
}
