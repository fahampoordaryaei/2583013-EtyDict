<?php

require_once __DIR__ . '/../../api/user.php';

$basePath = '/etydict/public/dictionary';

sessionHandler();
userLogout();

header('Location: ' . $basePath);
