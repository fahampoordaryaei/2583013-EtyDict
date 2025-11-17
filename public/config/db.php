<?php
$db_host = 'localhost';
$db_name = 'dictionary';
$db_user = 'root';
$db_pass = '';
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    error_log('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}