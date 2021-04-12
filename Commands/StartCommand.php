<?php


namespace Modules\TelegramBot\Commands;


use Modules\TelegramBot\Entities\TelegramUser;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Update;

class StartCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = "start";

    /**
     * @var string Command Description
     */
    protected $description = "Start Command to get you started";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $update = $this->getUpdate();
        $data = json_decode($update->getMessage()->getFrom());

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
            $this->replyWithMessage(['text' => 'Successfully authorized!']);
        }

        $commands = $this->getTelegram()->getCommands();

        $response = 'Hello! Your allowed list of commands: ' . "\n\n";
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        $this->replyWithMessage(['text' => $response]);
    }
}
