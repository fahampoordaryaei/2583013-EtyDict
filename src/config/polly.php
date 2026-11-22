<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

return [
    'access_key' => etydictSetting('AWS_ACCESS_KEY_ID'),
    'secret_key' => etydictSetting('AWS_SECRET_ACCESS_KEY'),
    'region' => etydictSetting('AWS_REGION', 'us-east-1'),
    'engine' => etydictSetting('POLLY_ENGINE', 'standard'),
    'voice_id' => etydictSetting('POLLY_VOICE_ID', 'Joanna'),
    'output_format' => etydictSetting('POLLY_OUTPUT_FORMAT', 'mp3'),
    'sample_rate' => etydictSetting('POLLY_SAMPLE_RATE', '22050'),
];
