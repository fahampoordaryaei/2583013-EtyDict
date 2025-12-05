<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/lib/input_filter.php';
require_once __DIR__ . '/../../api/user.php';
require_once __DIR__ . '/../../../src/repo/user_repo.php';

$basePath = '/';
$email_success = false;

sessionHandler();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = cleanToken($_GET['token'] ?? '');
    if ($token !== '') {
        $credentials = validateCredToken($token);
        if ($credentials !== null) {
            changeEmail($credentials['user_id'], $credentials['new_email']);
            expireCredToken($token);
            $email_success = true;
        }
    }
}

$_SESSION['email_success'] = $email_success;
header('Location: ' . $basePath . 'account/profile/');
exit();
