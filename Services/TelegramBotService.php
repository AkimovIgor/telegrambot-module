<?php

namespace Modules\TelegramBot\Services;

use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Modules\TelegramBot\Entities\TelegramUser;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class TelegramBotService
{
    protected $bot;

    public function __construct()
    {
        $this->bot = new Api(
            config('telegram.bots.mybot.token'),
            config('telegram.async_requests')
        );
    }

    public function getBotInfo()
    {
        return Telegram::getMe();
    }

    public function getUpdates()
    {
        if (env('APP_ENV') == 'p') {
            $updates = Telegram::getUpdates();
        } else {
            $updates = [Telegram::getWebhookUpdates()];
        }
        return $updates;
    }

    public function getWebhookInfo()
    {
        return Telegram::getWebhookInfo();
    }

    public function setWebhook($params)
    {
        try {
            $response = Telegram::setWebhook($params);
        } catch (\Exception $e) {
            return false;
        }

        $jsonResponse = json_decode($this->getWebhookInfo());
        if (isset($jsonResponse->url)) {
            DotenvEditor::setKey('TELEGRAM_WEBHOOK_URL', $jsonResponse->url);
            DotenvEditor::save();
        }

        return $response;
    }

    public function handleCommands()
    {
        if (env('APP_ENV') == 'p') {
            $update = Telegram::commandsHandler(false, ['timeout' => 20]);
        } else {
            $update = Telegram::commandsHandler(true);
        }
        return $update;
    }

    public function handleUpdates($updates)
    {

    }
}
