<?php


namespace Modules\TelegramBot\Entities;


use App\User;
use Illuminate\Database\Eloquent\Model;

class TelegramBot extends Model
{
    protected $table = 'telegram_bots';

    protected $guarded = [];

    protected $appends = ['settings'];

    public function getSettingsAttribute($value)
    {
        return json_decode($value);
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = json_encode($value);
    }
}
