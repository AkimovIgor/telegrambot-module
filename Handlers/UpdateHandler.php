<?php


namespace Modules\TelegramBot\Handlers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Role;
use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Entities\TelegramBotFlow;
use Modules\TelegramBot\Mails\ConfirmCode;
use WeStacks\TeleBot\Interfaces\UpdateHandler as BaseUpdateHandler;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardRemove;
use WeStacks\TeleBot\Objects\KeyboardButton;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Modules\TelegramBot\Entities\TelegramUser;

class UpdateHandler extends BaseUpdateHandler
{
    private static $name;

    protected $prevAction;

    public static function trigger(Update $update, TeleBot $bot)
    {
        return true;
    }

    public function handle()
    {
        $update = $this->update;

        $this->prevAction = $this->getBotFlow();

        $this->sendMessage([
            'text' => $prev ?? 'Empty'
        ]);

        $this->saveBotFlow();

        if (isset($update->callback_query)) {
            switch ($update->callback_query->data) {
                case 'register':
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id
                    ]);
                    $this->setBotFlow('register');
                    $this->sendMessage([
                        'text' => 'Введите Ваш email:'
                    ]);
                    break;
                case 'help':
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $this->getHelpCommand();
                    break;
                case (preg_match('/^setpassword/', $update->callback_query->data) ? true : false):
                    $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $this->generateNewPassword($searchUser->user_id ?? false, (int)str_replace('setpassword_', '', $update->callback_query->data));
                    break;
                case (preg_match('/^changepassword/', $update->callback_query->data) ? true : false):
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $this->changePassword($update->callback_query->data);
                    break;
                case (preg_match('/^passwordsuccess/', $update->callback_query->data) ? true : false):
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $this->successPassword($update->callback_query->data);
                    break;
                case (preg_match('/^setrole/', $update->callback_query->data) ? true : false):
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();
                    $this->generateNewPassword($searchUser->user_id, (int)str_replace('setrole_', '', $update->callback_query->data));
                    break;
                case (preg_match('/^sendcode/', $update->callback_query->data) ? true : false):
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id,
                    ]);
                    $email = str_replace('sendcode_', '', $update->callback_query->data);
                    $this->sendCodeToMail($email);
                    break;
            }
        }

        if (isset($update->message)) {
            switch ($update->message->text) {
                case filter_var($update->message->text, FILTER_VALIDATE_EMAIL):
                    $this->sendCodeToMail($update->message->text);
                    break;
                case (Str::contains($update->message->text, 'XpQR') && Str::contains($update->message->text, 'vP')):
                    $this->acceptConfirmationCode($update->message->text);
                    break;
            }
        }
    }

    protected function getUpdate()
    {
        return $this->update;
    }

    /**
     * @return bool
     */
    protected function saveBotFlow()
    {
        $update = $this->update;
        if (isset($update->callback_query)) {
            $chatId = $update->callback_query->from->id;
            $command = $update->callback_query->data;
        } else {
            $chatId = $update->message->from->id;
            $command = $update->message->text;
        }

        $flow = new TelegramBotFlow([
            'chat_id' => $chatId,
            'command' => $command
        ]);
        return $flow->save();
    }

    protected function getBotFlow()
    {
        $update = $this->update;
        if (isset($update->callback_query)) {
            $chatId = $update->callback_query->from->id;
        } else {
            $chatId = $update->message->from->id;
        }
        $flow = TelegramBotFlow::where('chat_id', $chatId)->orderBy('id', 'DESC')->first();
        return $flow->command ?? null;
    }

    protected function setBotFlow($command)
    {
        $update = $this->update;
        if (isset($update->callback_query)) {
            $chatId = $update->callback_query->from->id;
        } else {
            $chatId = $update->message->from->id;
        }
        $flow = new TelegramBotFlow([
            'chat_id' => $chatId,
            'command' => $command
        ]);
        return $flow->save();
    }

    /**
     * Подтвердить код подтверждения
     * @param $code
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function acceptConfirmationCode($code)
    {
        if (session('confirm_code')) {
            if (session('confirm_code') != $code) {
                $this->sendMessage([
                    'text' => 'Неправильный код подтверждения.'
                ]);
                return;
            }
            session()->remove('confirm_code');
            $this->setRole();
        } else {
            $this->sendMessage([
                'text' => 'Код подтверждения не найден.'
            ]);
        }
    }

    /**
     * Отправить код подтверждения на email
     * @param $email
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function sendCodeToMail($email)
    {
        if (preg_match("/^[a-z0-9\-\_]+\@([a-z0-9\-\_]+\.)+[a-z]{2,6}$/i", $email)) {

            $checkUser = User::where('email', $email)->first();
            if ($checkUser) {
                $this->sendMessage([
                    'text' => 'Пользователь с таким email уже зарегистрирован.'
                ]);
                return;
            }

            $keyboard = [
                [
                    (new InlineKeyboardButton([
                        'text' => 'Отправить код ещё раз',
                        'callback_data' => 'sendcode_' . $email
                    ]))
                ],
            ];

            $replyMarkup = ReplyKeyboardMarkup::create([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);

            try {
                $code = 'XpQR' .  Str::random(6) . 'vP';
                session()->put('confirm_code', $code);
                Mail::to($email)->send(new ConfirmCode($code));
                session()->put('telegram_user_email', $email);
                $this->sendMessage([
                    'text' => 'На Ваш email отправлен код подтверждения. Введите его далее:',
                    'reply_markup' => $replyMarkup
                ]);
            } catch (\Exception $e) {
                $this->sendMessage([
                    'text' => 'Введенный Вами email не существует.'
                ]);
            }
        } else {
            $this->sendMessage([
                'text' => 'Вы ошиблись при вводе email.'
            ]);
        }
    }

    /**
     * Задать новую роль пользователю
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function setRole()
    {
        $bot = TelegramBot::where(
            'bot_token',
            config('telebot.bots.' . config('telebot.default') . '.token')
        )->first();

        if ($bot && $bot->settings && $bot->settings->roles) {
            $roles = Role::whereIn('id', $bot->settings->roles)->get();
        } else {
            $roles = Role::where('name', '!=', 'admin')->get();
        }

        $kButtonts = [];

        foreach ($roles as $role) {
            $kButtonts[][] = (new InlineKeyboardButton([
                'text' => $role->display_name,
                'callback_data' => 'setrole_' . $role->id
            ]));
        }

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $kButtonts,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->sendMessage([
            'text' => "Выберите роль, под которой Вас авторизовать:",
            'reply_markup' => $replyMarkup
        ]);
    }

    /**
     * Изменить существующий пароль
     * @param $data
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function changePassword($data)
    {
        $value = str_replace('changepassword_', '', $data);

        if ($value == 'yes') {
            $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();
            if (!$searchUser->user_id) {
                $this->sendMessage([
                    'text' => "Вы не зарегистрированы в системе."
                ]);
            }
            $userId = $searchUser->user_id;
            $searchUser->update(['user_id' => $userId]);
            $this->generateNewPassword($userId);
        } else {
            $this->sendMessage([
                'text' => "Ваш пароль не был изменен."
            ]);
        }
    }

    /**
     * Сгенерировать новый пароль
     * @param bool $changed
     * @param int $role
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function generateNewPassword($changed = false, $role = 0)
    {
        $newPassword = Str::random(8);
        $changed = $changed ? "_{$changed}_{$role}" : "_0_{$role}";

        $callbackData = 'passwordsuccess_' . $newPassword . $changed;

        $genPasswKeyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Подтвердить',
                    'callback_data' => $callbackData
                ])),
                (new InlineKeyboardButton([
                    'text' => 'Генерировать',
                    'callback_data' => "setpassword_{$role}"
                ])),
            ],
        ];

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $genPasswKeyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->sendMessage([
            'text' => "Сгенерирован новый пароль:\n<b>" . $newPassword . "</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup
        ]);
    }

    /**
     * Подтвердить сгенерированный пароль
     * @param $data
     * @throws \WeStacks\TeleBot\Exception\TeleBotObjectException
     */
    protected function successPassword($data)
    {
        $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();
        $parts = explode('_', $data);
        $password = Hash::make($parts[1]);
        $name = $this->update->callback_query->from->first_name . ' ' . $this->update->callback_query->from->last_name;
        $email = session('telegram_user_email') ?? $searchUser->user->email;
        $role = (int)$parts[3];

        $changePasswKeyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Да',
                    'callback_data' => 'changepassword_yes'
                ])),
                (new InlineKeyboardButton([
                    'text' => 'Нет',
                    'callback_data' => 'changepassword_no'
                ])),
            ],
        ];

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $changePasswKeyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        if (!$searchUser->user_id && ! (int)$parts[2]) {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'telegram_user_id' => $this->update->callback_query->from->id
            ]);

            $user->save();

            if (isset($role) && $role) {
                $user->roles()->attach($role);
            }

            $searchUser->update(['user_id' => $user->id]);

            $this->sendMessage([
                'text' => "Пользователь успешно создан!\n\nВаши данные для авторизации:\n<b>Имя:</b> {$user->name}\n<b>Email:</b> {$user->email}\n<b>Пароль:</b> {$parts[1]}\n/info",
                'parse_mode' => 'HTML'
            ]);
        } elseif ($userId = (int)$parts[2]) {
            $user = User::find($userId);
            $user->password = $password;
            $user->email = $email;
            $user->telegram_user_id = $this->update->callback_query->from->id;
            $user->save();
            if (isset($role) && $role) {
                $user->roles()->detach($user->roles);
                $user->roles()->attach($role);
            }
            session()->remove('telegram_user_email');
            $this->sendMessage([
                'text' => "Пароль успешно изменен.\n\nВаши данные для авторизации:\n<b>Имя:</b> {$user->name}\n<b>Email:</b> {$user->email}\n<b>Пароль:</b> {$parts[1]}\n/info",
                'parse_mode' => 'HTML'
            ]);
        } else {
            $this->sendMessage([
                'text' => 'Вы уже зарегистрированы в системе! Изменить существующий пароль?',
                'reply_markup' => $replyMarkup
            ]);
        }
    }

    /**
     * Получить список существующих локальных команд
     */
    protected function getHelpCommand()
    {
        $commands = $this->bot->getLocalCommands();
        $response = 'Вам доступны следующие команды: ' . "\n\n";
        foreach ($commands as $name => $command) {
            if ($command->command != '/start') {
                $response .= sprintf('%s - %s' . PHP_EOL, $command->command, $command->description);
            }
        }
        $this->sendMessage([
            'text' => $response
        ]);
    }
}
