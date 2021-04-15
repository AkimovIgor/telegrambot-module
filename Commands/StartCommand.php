<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramUser;
use Modules\TelegramBot\Services\TelegramBotService;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class StartCommand extends CommandHandler
{
    protected $botService;

    protected static $aliases = [ '/start', '/s' ];

    protected static $description = 'Send "/start" or "/s" to get "Hello, World!"';

    public function __construct(TelegramBotService $telegramBotService, TeleBot $bot, Update $update)
    {
        $this->botService = $telegramBotService;
        parent::__construct($bot, $update);
    }

    public function handle()
    {
//        $update = $this->getUpdate();
//        $data = json_decode($update->getMessage()->getFrom());
//
//        if (! TelegramUser::whereChatId($data->id)->first()) {
//            $telegramUser = new TelegramUser([
//                'chat_id' => $data->id,
//                'is_bot' => $data->is_bot,
//                'first_name' => $data->first_name,
//                'last_name' => $data->last_name,
//                'username' => $data->username,
//                'language_code' => $data->language_code,
//            ]);
//
//            $telegramUser->save();
//            $this->replyWithMessage(['text' => 'Successfully authorized!']);
//        }
//
//        $commands = $this->getTelegram()->getCommands();
//
//        $response = 'Hello! Your allowed list of commands: ' . "\n\n";
//        foreach ($commands as $name => $command) {
//            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
//        }

        $this->sendMessage(['text' => 1]);
    }
}
