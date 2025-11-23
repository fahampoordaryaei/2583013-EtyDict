<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . '/../config/polly.php';
const SSML_TEMPLATE = '<speak><phoneme alphabet="ipa" ph="%s">X</phoneme></speak>';

use Aws\Exception\AwsException;
use Aws\Polly\PollyClient;
use Aws\Credentials\Credentials;


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$payload = json_decode(file_get_contents('php://input'), true);
$ipa = trim((string) ($payload['ipa'] ?? $_POST['ipa'] ?? $_GET['ipa']));
$ssml = sprintf(SSML_TEMPLATE, htmlspecialchars($ipa));

$clientOptions = [
    'region' => $config['region'],
    'version' => 'latest',
];
$clientOptions['credentials'] = new Credentials(
    $config['access_key'],
    $config['secret_key'],
);
$client = new PollyClient($clientOptions);

try {
    $response = $client->synthesizeSpeech([
        'Engine' => $config['engine'],
        'VoiceId' => $config['voice_id'],
        'OutputFormat' => $config['output_format'],
        'SampleRate' => $config['sample_rate'],
        'TextType' => 'ssml',
        'Text' => $ssml,
    ]);
} catch (AwsException $e) {
    http_response_code(502);
    echo 'AWS exception: Unable to synthesize speech';
    exit;
}

$audioStream = $response->get('AudioStream');
if ($audioStream === null) {
    http_response_code(502);
    echo 'No audio returned from Polly';
    exit;
}

$audioStream->rewind();
$streamResource = $audioStream->detach();
fpassthru($streamResource);
exit;
