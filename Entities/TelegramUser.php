<?php


namespace Modules\TelegramBot\Entities;


use App\User;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $table = 'telegram_users';

    protected $guarded = [];

    protected $appends = ['data'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }
}
