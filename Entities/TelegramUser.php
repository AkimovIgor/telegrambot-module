<?php


namespace Modules\TelegramBot\Entities;


use App\User;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $table = 'telegram_users';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
