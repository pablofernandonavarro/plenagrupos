<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRequirement extends Model
{
    protected $fillable = ['specialty', 'monthly_required_count'];

    protected $casts = [
        'monthly_required_count' => 'integer',
    ];

    public static function requiredCountFor(string $specialty): int
    {
        return (int) (static::where('specialty', $specialty)->value('monthly_required_count') ?? 1);
    }
}
