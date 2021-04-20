<?php


namespace Modules\TelegramBot\Mails;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class ConfirmCode extends Mailable
{
    use Queueable;

    protected $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->view('telegrambot::emails.send_code')
            ->with([
                'code' => $this->code
            ]);
    }
}
