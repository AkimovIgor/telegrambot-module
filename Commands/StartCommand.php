<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Entities\TelegramBotFlow;
use Modules\TelegramBot\Entities\TelegramUser;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class StartCommand extends CommandHandler
{
    protected static $aliases = [ '/start'];

    protected static $description = 'Запустить бота или поздороваться с ним.';

    protected $data;

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
    }

    public function handle()
    {
        $this->showRegister();
        if(!$user = TelegramUser::where('chat_id', $this->update->message->from->id)->first()) {
            $this->makeTelegramUser();
        }
        $this->data = $this->getBotFlow();
        $this->data['mode'] = 'register';
        $this->setBotFlow($this->data);
    }

    /**
     * @return array
     */
    protected function getBotFlow()
    {
        $res = [];
        $user = TelegramUser::where('chat_id', $this->update->message->from->id)->first();
        if ($user->data) {
            $res = $user->data;
        }
        return $res;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function setBotFlow($data = [])
    {
        if(!$user = TelegramUser::where('chat_id', $this->update->message->from->id)->first()) {
            $user = $this->makeTelegramUser();
        }
        return $user->update(['data' => $data]);
    }

    protected function makeTelegramUser()
    {
        $telegramUser = new TelegramUser([
            'chat_id' => $this->getChatUser()->id,
            'is_bot' => $this->getChatUser()->is_bot,
            'first_name' => $this->getChatUser()->first_name ?? null,
            'last_name' => $this->getChatUser()->last_name ?? null,
            'username' => $this->getChatUser()->username,
            'language_code' => $this->getChatUser()->language_code ?? null,
        ]);
        $telegramUser->save();
        return $telegramUser;
    }

    protected function getChatUser()
    {
        if (isset($this->update->callback_query)) {
            return $this->update->callback_query->from;
        }
        return $this->update->message->from;
    }

    protected function showRegister()
    {
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
