<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProfessionalUnavailability extends Model
{
    protected $fillable = [
        'professional_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /**
     * True si $date cae dentro del rango bloqueado. Si el bloque tiene start_time/end_time,
     * solo bloquea esa franja horaria del día; si son null, bloquea el día completo.
     */
    public function coversDate(Carbon $date): bool
    {
        return $date->toDateString() >= $this->start_date->toDateString()
            && $date->toDateString() <= $this->end_date->toDateString();
    }

    public function isFullDay(): bool
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }
}
