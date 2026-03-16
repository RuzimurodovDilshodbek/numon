<?php

return [
    'token' => env('TELEGRAM_BOT_TOKEN', ''),
    'webhook_url' => env('TELEGRAM_BOT_WEBHOOK_URL', ''),

    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),

    'polling' => [
        'timeout' => 10,
        'limit' => 100,
    ],
];
