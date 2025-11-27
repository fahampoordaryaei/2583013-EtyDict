<?php

require_once __DIR__ . '/../../api/user.php';

$basePath = '/etydict/public/';
$redirect = trim($_GET['redirect'] ?? '', '/');
sessionHandler();
userLogout();

$target = $redirect ? $redirect : '/';
header('Location: ' . $basePath . $target);