<?php

namespace Modules\TelegramBot\Services;

use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Modules\TelegramBot\Handlers\UpdateHandler;
use WeStacks\TeleBot\BotManager;

class TelegramBotService
{
    protected $bot;

    public function __construct()
    {
        $this->bot = new BotManager(config('telebot'));
    }

    public function getBot()
    {
        return $this->bot;
    }

    public function getBotData()
    {
        $update = $this->getUpdates();
        if (isset($update->callback_query)) {
            return $update->callback_query->data;
        }
        return $update->message->text;
    }

    public function getBotInfo()
    {
        return $this->bot->getMe();
    }

    public function getUpdates()
    {
        if (env('APP_ENV') == 'local') {
            $updates = [];
            $last_offset = 0;
            while (true) {
                $updates = $this->bot->getUpdates([
                    'offset' => $last_offset + 1
                ]);
                foreach ($updates as $update) {
                    $this->bot->handleUpdate($update);
                    $last_offset = $update->update_id;

                    $this->bot->callHandler(UpdateHandler::class, $update, true);
                }
            }
        } else {
            $updates = $this->bot->handleUpdate();
        }
        return $updates;
    }

    public function getWebhookInfo()
    {
        return $this->bot->getWebhookInfo();
    }

    public function setWebhook($params)
    {
        try {
            $response = $this->bot->setWebhook($params);
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
        return 1;
    }

    public function handleUpdates($updates)
    {

    }
}
