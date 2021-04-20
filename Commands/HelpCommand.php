<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramBot;
use Modules\TelegramBot\Entities\TelegramUser;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\InlineKeyboardButton;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class HelpCommand extends CommandHandler
{
    protected static $aliases = [ '/help'];

    protected static $description = 'Посмотреть список существующих команд.';

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
    }

    public function handle()
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
