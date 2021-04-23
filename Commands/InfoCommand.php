<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Entities\TelegramUser;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class InfoCommand extends CommandHandler
{
    protected static $aliases = [ '/info'];

    protected static $description = 'Посмотреть информацию о вас в базе данных.';

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
    }

    public function handle()
    {
        $tgUser = TelegramUser::whereChatId($this->update->message->from->id)->first();
        $response = '';
        if ($tgUser && $tgUser->user) {
            $response .= '<b>Имя:</b> ' . $tgUser->user->name . "\n";
            $response .= '<b>Email:</b> ' . $tgUser->user->email . "\n";
            $response .= '<b>Роли:</b> ';
            foreach ($tgUser->user->roles as $role) {
                $response .= $role->display_name . ', ';
            }
            $response = trim($response, ', ');
            $response .= "\n/help";
        } else {
            $response .= 'Вы не зарегистрированы в системе. /register';
            $keyboard = [
                [
                    (new InlineKeyboardButton([
                        'text' => 'Регистрация',
                        'callback_data' => 'register'
                    ])),
                ],
            ];

            $replyMarkup = ReplyKeyboardMarkup::create([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
            $this->sendMessage([
                'text' => $response,
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
            return;
        }
        $this->sendMessage([
            'text' => $response,
            'parse_mode' => 'HTML'
        ]);
    }
}
