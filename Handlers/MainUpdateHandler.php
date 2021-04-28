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

class MainUpdateHandler extends BaseUpdateHandler
{
    protected $data;

    public static function trigger(Update $update, TeleBot $bot)
    {
        if (isset($update->message)) {
            $data = $update->message->text ?? '';
        } else {
            $data = $update->callback_query->data;
        }
        $commands = [];
        foreach ($bot->getLocalCommands() as $command) {
            $commands[] = $command->command;
        }
        return !in_array($data, $commands);
    }

    public function handle()
    {
        if(!$tgUser = TelegramUser::where('chat_id', $this->getChatId())->first()){
            $this->makeTelegramUser();
        }

        $this->data = $this->getBotFlow();

        if (!isset($this->data['mode'])) {
            $mode = 'start';
        } else {
            $mode = $this->data['mode'];
        }

        if(isset($this->update->callback_query) && $this->getBotData() == 'register'){
            $mode = "register";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->sendMessage([
                'text' => 'Введите Ваш email:'
            ]);
            $this->data["mode"] = "set_email";
            $this->setBotFlow($this->data);
            return;
        }

        if(isset($this->update->callback_query) && $this->getBotData() == "send_code_again"){
            $mode = "send_code_again";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->sendCodeToMail($this->data['email']);
            return;
        }

        if(isset($this->update->callback_query) && $this->getBotData() == "set_password"){
            $mode = "set_password";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->setPassword();
            $this->setBotFlow($this->data);
            return;
        }

        if(isset($this->update->callback_query) && $this->getBotData() == "success_password"){
            $mode = "success_password";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->setRole();
            $this->setBotFlow($this->data);
            return;
        }

        if(isset($this->update->callback_query) && Str::contains($this->getBotData(), 'set_role')){
            $mode = "set_role";
            $role = str_replace('set_role_', '', $this->getBotData());
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->data["role"] = $role;
            $this->data["mode"] = 'success';
            $this->setBotFlow($this->data);
            $mode = 'success';
        }

        if(isset($this->update->callback_query) && $this->getBotData() == "reset_data"){
            $mode = "reset_data";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->successRegistration(true);
            $this->setBotFlow($this->data);
            return;
        }

        if(isset($this->update->callback_query) && $this->getBotData() == "reset_data_false"){
            $mode = "reset_data_false";
            $this->answerCallbackQuery([
                'callback_query_id' => $this->update->callback_query->id,
            ]);
            $this->sendMessage([
                'text' => 'Данные не были изменены.',
            ]);
            return;
        }

        if($mode == "set_email"){
            $this->data['email'] = $this->getBotData();
            $this->sendCodeToMail($this->data['email']);
            $this->setBotFlow($this->data);
            return;
        }

        if($mode == "send_confirm_code"){
            $this->data['confirm_code'] = $this->getBotData();
            $this->acceptConfirmCode();
            $this->setBotFlow($this->data);
            return;
        }

        if($mode == "password"){
            $this->data['password'] = $this->getBotData();
            if (strlen($this->data['password']) < 8 || strlen($this->data['password']) > 60) {
                $this->sendMessage([
                    'text' => "Пароль должен быть не менее 8 символов и не более 60."
                ]);
                return;
            }
            $this->requestPassword();
            $this->data["mode"] = "set_role";
            $this->setBotFlow($this->data);
            return;
        }

        if($mode == "success"){
            $this->successRegistration();
            $this->data["mode"] = "register";
            $this->setBotFlow($this->data);
            return;
        }

        $this->sendMessage([
            'text' => "Вы явно ошиблись при вводе. /help"
        ]);
    }

    protected function requestPassword()
    {
        $keyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Подтвердить',
                    'callback_data' => 'success_password'
                ])),
                (new InlineKeyboardButton([
                    'text' => 'Изменить',
                    'callback_data' => 'set_password'
                ])),
            ],
        ];

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        $this->sendMessage([
            'text' => "Ваш новый пароль:\n<b>{$this->data['password']}</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup,
        ]);
    }

    protected function setPassword()
    {
        $this->sendMessage([
            'text' => 'Придумайте пароль:'
        ]);
        $this->data["mode"] = "password";
    }

    protected function acceptConfirmCode()
    {
        if ($this->data['confirm_code'] == $this->data['code']) {
            $this->sendMessage([
                'text' => 'Код успешно принят',
            ]);
            $this->data["mode"] = "set_password";
            $this->setPassword();
        } else {
            $this->sendMessage([
                'text' => 'Неправильный код подтверждения /help',
            ]);
        }
    }

    protected function makeTelegramUser()
    {
        $telegramUser = new TelegramUser([
            'chat_id' => $this->getChatUser()->id,
            'is_bot' => $this->getChatUser()->is_bot,
            'first_name' => $this->getChatUser()->first_name,
            'last_name' => $this->getChatUser()->last_name,
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

    /**
     * @return string
     */
    protected function getBotData()
    {
        if (isset($this->update->callback_query)) {
            return $this->update->callback_query->data;
        }
        return $this->update->message->text ?? '';
    }

    /**
     * @return int
     */
    protected function getChatId()
    {
        if (isset($this->update->callback_query)) {
            return $this->update->callback_query->from->id;
        }
        return $this->update->message->from->id;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function setBotFlow($data = [])
    {
        $user = TelegramUser::where('chat_id', $this->getChatId())->first();
        return $user->update(['data' => $data]);

    }

    /**
     * @return array
     */
    protected function getBotFlow()
    {
        $res = [];
        $user = TelegramUser::where('chat_id', $this->getChatId())->first();
        if ($user->data) {
            $res = $user->data;
        }
        return $res;
    }

    protected function sendCodeToMail($email)
    {
        if (preg_match("/^[a-z0-9\-\_]+\@([a-z0-9\-\_]+\.)+[a-z]{2,6}$/i", $email)) {

            $checkUser = User::where('email', $email)->first();
            $tgUser = TelegramUser::where('chat_id', $this->getChatId())->first();
            if ($checkUser) {
                if ($tgUser &&  $tgUser->user_id == $checkUser->id) {
                    if ($this->data['email'] == $checkUser->email) {
                        $keyboard = [
                            [
                                (new InlineKeyboardButton([
                                    'text' => 'Изменить данные',
                                    'callback_data' => 'set_password'
                                ]))
                            ],
                        ];
                        $replyMarkup = ReplyKeyboardMarkup::create([
                            'inline_keyboard' => $keyboard,
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true
                        ]);
                        $this->sendMessage([
                            'text' => "Вы уже зарегистрированы в системе!\n/info",
                            'reply_markup' => $replyMarkup
                        ]);
                    } else {
                        $keyboard = [
                            [
                                (new InlineKeyboardButton([
                                    'text' => 'Да',
                                    'callback_data' => 'reset_data'
                                ])),
                                (new InlineKeyboardButton([
                                    'text' => 'Нет',
                                    'callback_data' => 'reset_data_false'
                                ])),
                            ],
                        ];
                        $replyMarkup = ReplyKeyboardMarkup::create([
                            'inline_keyboard' => $keyboard,
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true
                        ]);

                        $this->sendMessage([
                            'text' => "Вы уже зарегистрированы в системе!\nИзменить существующие данные?\n/info",
                            'reply_markup' => $replyMarkup
                        ]);
                    }
                } else {
                    $this->sendMessage([
                        'text' => 'Пользователь с таким email уже зарегистрирован.'
                    ]);
                }

                $this->data['mode'] = 'set_email';
                $this->setBotFlow($this->data);
                return;
            }

            $keyboard = [
                [
                    (new InlineKeyboardButton([
                        'text' => 'Отправить код ещё раз',
                        'callback_data' => 'send_code_again'
                    ]))
                ],
            ];

            $replyMarkup = ReplyKeyboardMarkup::create([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);

            try {
                $code = Str::random(8);
                $this->sendMessage([
                    'text' => 'Отправляем письмо. Подождите...',
                ]);
                Mail::to($email)->send(new ConfirmCode($code));
                $this->data["code"] = $code;
                $this->data["mode"] = "send_confirm_code";
                $this->setBotFlow($this->data);
                $this->sendMessage([
                    'text' => 'На Ваш email отправлен код подтверждения. Введите его далее:',
                    'reply_markup' => $replyMarkup
                ]);
            } catch (\Exception $e) {
                $this->setBotFlow('register');
                $this->sendMessage([
                    'text' => 'Введенный Вами email не существует.'
                ]);
            }
        } else {
            $this->setBotFlow('register');
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
                'callback_data' => 'set_role_' . $role->id
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
     * Подтвердить сгенерированный пароль
     */
    protected function successRegistration($changed = false)
    {
        $searchUser = TelegramUser::where('chat_id', $this->getChatId())->first();

        if (!$searchUser->user_id) {

            $name = $this->update->callback_query->from->first_name . ' ' . $this->update->callback_query->from->last_name;
            $email = $this->data['email'];
            $password = Hash::make($this->data['password']);
            $role = $this->data['role'];

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
                'text' => "Вы успешно зарегистрированы!\n\nВаши данные для авторизации:\n<b>Имя:</b> {$user->name}\n<b>Email:</b> {$user->email}\n<b>Пароль:</b> {$this->data['password']}\n/info",
                'parse_mode' => 'HTML'
            ]);
            $this->clearSecretData();
        } elseif ($searchUser->user_id && $changed) {
            $user = User::find($searchUser->user_id);
            $email = $this->data['email'];
            $password = $this->data['password'] ? Hash::make($this->data['password']) : $user->password;
            $role = $this->data['role'];
            $user->password = $password;
            $user->email = $email;
            $user->telegram_user_id = $this->update->callback_query->from->id;
            $user->save();
            if (isset($role) && $role) {
                $user->roles()->detach($user->roles);
                $user->roles()->attach($role);
            }
            $this->sendMessage([
                'text' => "Данные успешно изменены.\n\nВаши данные для авторизации:\n<b>Имя:</b> {$user->name}\n<b>Email:</b> {$user->email}\n<b>Пароль:</b> {$this->data['password']}\n/info",
                'parse_mode' => 'HTML'
            ]);
            $this->clearSecretData();
        } else {
            $keyboard = [
                [
                    (new InlineKeyboardButton([
                        'text' => 'Да',
                        'callback_data' => 'reset_data'
                    ])),
                    (new InlineKeyboardButton([
                        'text' => 'Нет',
                        'callback_data' => 'reset_data_false'
                    ])),
                ],
            ];

            $replyMarkup = ReplyKeyboardMarkup::create([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);

            $this->sendMessage([
                'text' => "Вы уже зарегистрированы в системе!\nИзменить существующие данные?\n/info",
                'reply_markup' => $replyMarkup
            ]);
        }

    }

    protected function clearSecretData()
    {
        $this->data['password'] = '';
        $this->data['code'] = '';
        $this->data['confirm_code'] = '';
        $this->setBotFlow($this->data);
    }
}
