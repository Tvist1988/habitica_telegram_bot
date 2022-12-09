<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'habitica_id',
        'habitica_key',
    ];

    protected $hidden = [
        'habitica_key',
    ];
}
