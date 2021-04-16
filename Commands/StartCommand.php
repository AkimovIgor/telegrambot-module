<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramUser;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\BotCommand;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class StartCommand extends CommandHandler
{
    protected static $aliases = [ '/start'];

    protected static $description = 'Запустить бота или поздороваться с ним.';

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
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'username' => $data->username,
                'language_code' => $data->language_code,
            ]);

            $telegramUser->save();
            $this->sendMessage(['text' => 'Привет! Я тебя запомнил в системе)']);
        }

        $keyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Регистрация',
                    'callback_data' => 'register'
                ])),
                (new InlineKeyboardButton([
                    'text' => 'Помощь',
                    'callback_data' => 'help'
                ])),
            ],
        ];

        $replyMarkupRemove = ReplyKeyboardMarkup::create([
            'remove_keyboard' => true
        ]);

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->sendMessage([
            'text' => "Привет, я бот для регистрации в системе ALTRP.\nЧтобы зарегистрироваться, нажми на кнопку 'Регистрация'.",
            'reply_markup' => $replyMarkup
        ]);
    }
}
