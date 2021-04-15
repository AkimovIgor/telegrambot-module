<?php

return [
    'default' => 'akimigor_bot',
    'bots' => [
        'akimigor_bot' => [
            'token'         => env('TELEGRAM_BOT_TOKEN', '<telegram api token>'),
            'api_url'       => 'https://api.telegram.org',
            'exceptions'    => true,
            'async'         => env('TELEGRAM_ASYNC_REQUESTS', false),
            'webhook' => [
                 'url'               => env('TELEGRAM_WEBHOOK_URL', env('APP_URL').'/telebot/webhook/bot/'.env('TELEGRAM_BOT_TOKEN')),
                // 'certificate'       => env('TELEGRAM_BOT_CERT_PATH', storage_path('app/ssl/public.pem')),
                // 'max_connections'   => 40,
                 'ip_address'        => '3.134.125.175',
                // 'allowed_updates'   => ["message", "edited_channel_post", "callback_query"]
            ],
            'handlers'      => [
                \Modules\TelegramBot\Commands\StartCommand::class
            ],
//            'poll'    => [
//                'limit'             => 100,
//                'timeout'           => 0,
//                'allowed_updates'   => ["message", "edited_channel_post", "callback_query"]
//            ],
        ],
//        'bot2' => [
//            'token'         => env('TELEGRAM_BOT2_TOKEN', '<telegram api token>')
//        ]
    ]
];
