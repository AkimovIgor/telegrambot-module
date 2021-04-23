<?php

return [
    'default' => env('TELEGRAM_BOT_NAME', 'bot_1'),
    'bots' => [
        env('TELEGRAM_BOT_NAME', 'bot_1') => [
            'token'         => env('TELEGRAM_BOT_TOKEN', ''),
            'api_url'       => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
            'exceptions'    => env('TELEGRAM_BOT_DEBUG', false),
            'async'         => env('TELEGRAM_ASYNC_REQUESTS', false),
            'webhook' => [
                 'url'               => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') . '/telegrambot/webhook'),
                 'certificate'       => env('TELEGRAM_BOT_CERT_PATH', storage_path('app/ssl/public.pem')),
                 'max_connections'   => env('TELEGRAM_MAX_CONNECTIONS', 40),
                 'ip_address'        => env('TELEGRAM_IP_ADDRESS', ''),
                 'allowed_updates'   => ["message", "edited_channel_post", "callback_query"]
            ],
            'handlers'      => [
                \Modules\TelegramBot\Commands\StartCommand::class,
                \Modules\TelegramBot\Commands\HelpCommand::class,
                \Modules\TelegramBot\Commands\RegisterCommand::class,
                \Modules\TelegramBot\Commands\InfoCommand::class,
//                \Modules\TelegramBot\Handlers\UpdateHandler::class
                \Modules\TelegramBot\Handlers\MainUpdateHandler::class
            ],
            'poll'    => [
                'limit'             => 100,
                'timeout'           => 0,
                'allowed_updates'   => ["message", "edited_channel_post", "callback_query"]
            ],
        ],
//        'bot2' => [
//            'token'         => env('TELEGRAM_BOT2_TOKEN', '<telegram api token>')
//        ]
    ]
];
