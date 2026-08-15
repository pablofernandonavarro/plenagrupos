<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name'];

    protected $casts = [
        'date' => 'date',
    ];

    public static function fallsOn(Carbon $date): bool
    {
        return self::whereDate('date', $date->toDateString())->exists();
    }
}
