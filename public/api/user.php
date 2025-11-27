<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/api/user.php';

switch (basename($_SERVER['SCRIPT_FILENAME'])) {
	case 'script.js':
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$payload = [
				'authenticated' => checkAuth(),
			];
			giveJson($payload);
			exit;
		}
		break;

	case basename(__FILE__):
		apiHandler();
		break;
}
