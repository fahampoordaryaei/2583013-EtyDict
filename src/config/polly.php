<?php

declare(strict_types=1);

return [
    'region' => getenv('AWS_REGION') ?: 'eu-central-1',
    'voice_id' => getenv('AWS_POLLY_VOICE_ID') ?: 'Amy',
    'engine' => getenv('AWS_POLLY_ENGINE') ?: 'standard',
    'output_format' => getenv('AWS_POLLY_FORMAT') ?: 'mp3',
    'sample_rate' => getenv('AWS_POLLY_SAMPLE_RATE') ?: '24000',
    'access_key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
    'secret_key' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
];
