<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/api/user.php';
require_once __DIR__ . '/../src/lib/input_filter.php';
require_once __DIR__ . '/../src/config/recaptcha.php';
require_once __DIR__ . '/../src/api/json.php';
require_once __DIR__ . '/../src/log/eventlogger.php';
require_once __DIR__ . '/../src/config/security.php';

header('Content-Type: application/json');

requireCsrfToken();

$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (!verifyRecaptcha($recaptchaResponse)) {
    http_response_code(400);
    giveLogEvent('reset_password_api_recaptcha_failed', 400, 'reCAPTCHA verification failed');
    exit;
}

$email = cleanEmail($_POST['email'] ?? '');
if ($email === '') {
    http_response_code(400);
    giveLogEvent('reset_password_api_invalid_email', 400, 'Invalid email address');
    exit;
}

$userId = getUserIdByEmail($email);
$response = ['success' => true];

if ($userId !== null) {
    $generatedToken = generateCredToken($userId, null);
    if ($generatedToken !== null) {
        ob_start();
        $_GET['token'] = $generatedToken['token'];
        $_GET['username'] = $generatedToken['username'];
        include __DIR__ . '/../src/email/reset-password.php';
        $emailContent = ob_get_clean();

        $response['download'] = [
            'filename' => 'reset-password-email.html',
            'content' => base64_encode($emailContent)
        ];
    }
}

echo json_encode($response);
exit;
