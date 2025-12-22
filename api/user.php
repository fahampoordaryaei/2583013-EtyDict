<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/api/user.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['filename'] ?? '') === 'script.js') {
	$payload = [
		'authenticated' => checkAuth(),
	];
	giveJson($payload);
	exit;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
	apiHandler();
}
