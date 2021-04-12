<?php


namespace Modules\TelegramBot\Entities;


use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $table = 'telegram_users';

    protected $guarded = [];
}
