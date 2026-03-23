<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'meeting_day', 'meeting_time',
        'recurrence_type', 'recurrence_interval', 'recurrence_end_date',
        'auto_sessions', 'admin_id', 'qr_token', 'active', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'active'              => 'boolean',
        'auto_sessions'       => 'boolean',
        'recurrence_interval' => 'integer',
        'recurrence_end_date' => 'date',
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
    ];

    // Backwards-compat: derive auto_sessions from recurrence_type
    public function getAutoSessionsAttribute(): bool
    {
        return ($this->attributes['recurrence_type'] ?? 'none') !== 'none';
    }

    // 'pending' | 'active' | 'closed'
    public function getStatusAttribute(): string
    {
        if ($this->active) return 'active';
        if ($this->started_at) return 'closed';
        return 'pending';
    }

    public function getMeetingTimeFormattedAttribute(): ?string
    {
        if (!$this->meeting_time) return null;
        return date('H:i', strtotime($this->meeting_time));
    }

    /**
     * Human-readable recurrence label, e.g. "Semanal · Lunes", "Cada 2 semanas · Lunes", "Mensual"
     */
    public function getRecurrenceLabelAttribute(): string
    {
        $n = $this->recurrence_interval ?? 1;
        return match ($this->recurrence_type) {
            'daily'   => $n > 1 ? "Cada {$n} días" : 'Diario',
            'weekly'  => ($n > 1 ? "Cada {$n} semanas" : 'Semanal') . ($this->meeting_day ? " · {$this->meeting_day}" : ''),
            'monthly' => $n > 1 ? "Cada {$n} meses" : 'Mensual',
            'yearly'  => $n > 1 ? "Cada {$n} años" : 'Anual',
            default   => 'Sin repetición',
        };
    }

    public function getNextSessionAtAttribute(): ?Carbon
    {
        if (($this->attributes['recurrence_type'] ?? 'none') === 'none') return null;
        if (!$this->meeting_time) return null;

        $tz  = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);
        [$hour, $minute] = explode(':', $this->meeting_time);

        switch ($this->attributes['recurrence_type']) {
            case 'daily':
                return $now->copy()->addDay()->setTime((int)$hour, (int)$minute);

            case 'weekly':
                if (!$this->meeting_day) return null;
                $dayMap = [
                    'Domingo' => Carbon::SUNDAY,    'Lunes'     => Carbon::MONDAY,
                    'Martes'  => Carbon::TUESDAY,   'Miércoles' => Carbon::WEDNESDAY,
                    'Jueves'  => Carbon::THURSDAY,  'Viernes'   => Carbon::FRIDAY,
                    'Sábado'  => Carbon::SATURDAY,
                ];
                $targetDay = $dayMap[$this->meeting_day] ?? null;
                if ($targetDay === null) return null;
                if ($now->dayOfWeek === $targetDay) {
                    $today = $now->copy()->setTime((int)$hour, (int)$minute);
                    if ($now->lt($today)) return $today;
                }
                return $now->next($targetDay)->setTime((int)$hour, (int)$minute);

            case 'monthly':
                return $now->copy()->addMonth()->setTime((int)$hour, (int)$minute);

            case 'yearly':
                return $now->copy()->addYear()->setTime((int)$hour, (int)$minute);
        }

        return null;
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($group) {
            if (empty($group->qr_token)) {
                $group->qr_token = Str::uuid();
            }
        });
    }

    public function admin()       { return $this->belongsTo(User::class, 'admin_id'); }
    public function coordinators(){ return $this->belongsToMany(User::class, 'group_coordinator'); }
    public function patients()    { return $this->belongsToMany(User::class, 'group_patient')->withPivot('joined_at', 'maintenance_weight'); }
    public function attendances() { return $this->hasMany(GroupAttendance::class); }
    public function weightRecords(){ return $this->hasMany(WeightRecord::class); }
}
