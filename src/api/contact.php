<?php

declare(strict_types=1);

require_once __DIR__ . '/../repo/contact_repo.php';
require_once __DIR__ . '/../log/eventlogger.php';
require_once __DIR__ . '/../api/json.php';
require_once __DIR__ . '/../lib/input_filter.php';
require_once __DIR__ . '/../config/recaptcha.php';
require_once __DIR__ . '/../config/security.php';

requireCsrfToken();

$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (!verifyRecaptcha($recaptchaResponse)) {
    http_response_code(400);
    giveLogEvent('contact_api_recaptcha_failed', 400, 'Contact API: reCAPTCHA verification failed');
}

$name = cleanText($_POST['name'] ?? '');
$email = cleanEmail($_POST['email'] ?? '');
$subject = cleanText($_POST['subject'] ?? '', 150);
$message = cleanText($_POST['message'] ?? '', 1000);

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(400);
    giveLogEvent('contact_api_missing_fields', 400, 'Contact API: missing required fields');
}

$success = submitMessage($name, $email, $subject, $message);
if (!$success) {
    http_response_code(500);
    giveLogEvent('contact_api_submission_failed', 500, 'Contact API: failed to submit message');
}

giveJson(['status' => 'success', 'message' => 'Message submitted successfully']);
exit;
