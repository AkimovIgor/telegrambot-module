<?php


namespace Modules\TelegramBot\Handlers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Role;
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

    public static function trigger(Update $update, TeleBot $bot)
    {
        return true;
    }

    public function handle()
    {
        $update = $this->update;
        $bot = $this->bot;

        if (isset($update->callback_query)) {
            switch ($update->callback_query->data) {
                case 'register':
                    $this->answerCallbackQuery([
                        'callback_query_id' => $update->callback_query->id
                    ]);
                    $this->setRole();
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
                    $this->generateNewPassword($searchUser->user_id, (int)str_replace('setpassword_', '', $update->callback_query->data));
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
            }
        }
    }

    protected function setRole()
    {
        $roles = Role::where('name', '!=', 'admin')->get();
        $count = floor($roles->count() / 2);
        $kRows = [];
        $kButtonts = [];

        // for ($i = 0; $i < $count - 1; $i++) {
        //     $kButtonts[] =
        //     if ($i % 2 == 0) {

        //     }
        // }

        $i = 0;

        foreach ($roles as $role) {
            if ($i % 2 == 0) {
                $kRows[] = $kButtonts;
                $kButtonts = [];
            }
            $kButtonts[] = (new InlineKeyboardButton([
                'text' => $role->display_name,
                'callback_data' => 'setrole_' . $role->id
            ]));
            $i++;
        }

        $replyMarkup = ReplyKeyboardMarkup::create([
            'inline_keyboard' => $kRows,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->sendMessage([
            'text' => "Выбери роль, под которой тебя авторизовать:",
            'reply_markup' => $replyMarkup
        ]);
    }

    protected function changePassword($data)
    {
        $value = str_replace('changepassword_', '', $data);

        if ($value == 'yes') {
            $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();
            $userId = $searchUser->user_id;
            $searchUser->update(['user_id' => $userId]);
            $this->generateNewPassword($userId);
        } else {
            $this->sendMessage([
                'text' => "Ваш пароль не был изменен."
            ]);
        }
    }

    protected function generateNewPassword($changed = false, $role = 0)
    {
        $newPassword = Str::random(8);
        $changed = $changed ? "_{$changed}_{$role}" : "_0_{$role}";

        $callbackData = 'passwordsuccess_' . $newPassword . $changed;

        // \Log::info($callbackData);

        $genPasswKeyboard = [
            [
                (new InlineKeyboardButton([
                    'text' => 'Подтвердить',
                    'callback_data' => $callbackData
                ])),
                (new InlineKeyboardButton([
                    'text' => 'Cгенерировать новый',
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
            'text' => "Сгенерирован новый пароль: " . $newPassword . "",
            'reply_markup' => $replyMarkup
        ]);
    }

    protected function successPassword($data)
    {
        $parts = explode('_', $data);
        $password = Hash::make($parts[1]);
        $name = $this->update->callback_query->from->first_name . ' ' . $this->update->callback_query->from->last_name;
        $email = 'user' . Str::random(6) . '@gmail.com';
        $role = (int)$parts[3];

        $searchUser = TelegramUser::where('chat_id', $this->update->callback_query->from->id)->first();

        // $replyMarkupRemove = ReplyKeyboardRemove::create([
        //     'remove_keyboard' => true
        // ]);

        // \Log::info((int)$parts[2]);

        // $this->sendMessage([
        //     'text' => 'Пароль успешно изменен'
        // ]);

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
                'password' => $password
            ]);

            $user->save();

            if (isset($role) && $role) {
                $user->roles()->attach($role);
            }

            // \Log::info(self::$role);

            $searchUser->update(['user_id' => $user->id]);

            $this->sendMessage([
                'text' => "Пользователь успешно создан!\n\nВаши данные для авторизации:\nИмя: {$user->name}\nEmail: {$user->email}\nПароль: {$parts[1]}",
                // 'reply_markup' => $replyMarkupRemove
            ]);
        } elseif ($userId = (int)$parts[2]) {
            $user = User::find($userId);
            $user->password = $password;
            $user->save();
            if (isset($role) && $role) {
                $user->roles()->detach($user->roles);
                $user->roles()->attach($role);
            }
            $this->sendMessage([
                'text' => "Пароль успешно изменен.\n\nВаши данные для авторизации:\nИмя: {$user->name}\nEmail: {$user->email}\nПароль: {$parts[1]}"
            ]);
        } else {
            $this->sendMessage([
                'text' => 'Вы уже зарегистрированы в системе! Изменить существующий пароль?',
                'reply_markup' => $replyMarkup
            ]);
        }
    }

    protected function getHelpCommand()
    {
        $commands = $this->bot->getLocalCommands();
        $response = 'Тебе доступны следующие команды: ' . "\n\n";
        foreach ($commands as $name => $command) {
            $response .= sprintf('%s - %s' . PHP_EOL, $command->command, $command->description);
        }
        $this->sendMessage([
            'text' => $response
        ]);
    }
}
