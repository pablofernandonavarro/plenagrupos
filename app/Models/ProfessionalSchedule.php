<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalSchedule extends Model
{
    protected $fillable = [
        'professional_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'slot_duration_minutes' => 'integer',
    ];

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}
