<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Entities\TelegramUser;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class RegisterCommand extends CommandHandler
{
    protected static $aliases = [ '/register'];

    protected static $description = 'Пройти регистрацию.';

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
    }

    public function handle()
    {
        $update = $this->update;
        $data = $update->message->from;

        if (! TelegramUser::whereChatId($data->id)->first()) {
            $telegramUser = new TelegramUser([
                'chat_id' => $data->id,
                'is_bot' => $data->is_bot,
                'first_name' => $data->first_name ?? null,
                'last_name' => $data->last_name ?? null,
                'username' => $data->username,
                'language_code' => $data->language_code ?? null,
            ]);

            $telegramUser->save();
        }

        $keyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Регистрация',
                    'callback_data' => 'register'
                ]))
            ],
        ];

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $message = '';

        $bot = TelegramBot::where(
            'bot_token',
            config('telebot.bots.' . config('telebot.default') . '.token')
        )->first();

        if ($bot && $bot->settings && $bot->settings->start_message) {
            $message = $bot->settings->start_message;
        }

        $this->sendMessage([
            'text' => $message,
            'reply_markup' => $replyMarkup
        ]);
    }
}
