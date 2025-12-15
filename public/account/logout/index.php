<?php

declare(strict_types=1);

require_once __DIR__ . '/../../api/user.php';

$basePath = '/';
$redirect = trim($_GET['redirect'] ?? '', '/');
sessionHandler();
userLogout();

if ($redirect !== '') {
    header('Location: ' . $basePath . $redirect);
} else {
    header('Location: ' . $basePath);
}
exit;