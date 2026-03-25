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
        'name', 'modality', 'group_type', 'description', 'meeting_day', 'meeting_days', 'meeting_time',
        'session_duration_minutes',
        'recurrence_type', 'recurrence_interval', 'recurrence_end_date',
        'auto_sessions', 'admin_id', 'qr_token', 'active', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'auto_sessions' => 'boolean',
        'recurrence_interval' => 'integer',
        'recurrence_end_date' => 'date',
        'session_duration_minutes' => 'integer',
        'meeting_days' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Backwards-compat: derive auto_sessions from recurrence_type
    public function getAutoSessionsAttribute(): bool
    {
        return ($this->attributes['recurrence_type'] ?? 'none') !== 'none';
    }

    // 'pending' | 'active' | 'closed'
    public function getStatusAttribute(): string
    {
        $type = $this->attributes['recurrence_type'] ?? 'none';

        // Non-recurring groups: honour the manual active flag
        if ($type === 'none') {
            if ($this->attributes['active']) {
                return 'active';
            }
            if ($this->getRawOriginal('started_at')) {
                return 'closed';
            }

            return 'pending';
        }

        // Recurring groups: compute from schedule — no cron needed
        if ($this->recurrence_end_date &&
            Carbon::now('America/Argentina/Buenos_Aires')->startOfDay()->gt($this->recurrence_end_date)) {
            return 'closed';
        }

        return $this->isCurrentlyInSession() ? 'active' : 'pending';
    }

    /** True when the current wall-clock time is inside today's session window. */
    private function isCurrentlyInSession(): bool
    {
        if (! $this->meeting_time) {
            return false;
        }

        $tz = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);

        if (! $this->isTodayMeetingDay($now)) {
            return false;
        }

        [$h, $m] = array_pad(explode(':', $this->meeting_time), 2, '0');
        $start = $now->copy()->setTime((int) $h, (int) $m, 0);
        $end = $start->copy()->addMinutes($this->attributes['session_duration_minutes'] ?? 120);

        return $now->between($start, $end);
    }

    /** True when $date falls on a scheduled meeting day for this group. */
    private function isTodayMeetingDay(Carbon $date): bool
    {
        $type = $this->attributes['recurrence_type'] ?? 'none';
        $interval = max(1, (int) ($this->attributes['recurrence_interval'] ?? 1));
        $ref = Carbon::parse($this->getRawOriginal('created_at'))->startOfDay();

        if ($type === 'daily') {
            return (int) $ref->diffInDays($date) % $interval === 0;
        }

        if ($type === 'weekly') {
            $days = $this->meeting_days ?? ($this->attributes['meeting_day'] ? [$this->attributes['meeting_day']] : []);
            $dayMap = ['Domingo' => 0, 'Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6];
            $dayNums = array_values(array_filter(
                array_map(fn ($d) => $dayMap[$d] ?? null, $days),
                fn ($d) => $d !== null
            ));
            if (! in_array($date->dayOfWeek, $dayNums, true)) {
                return false;
            }
            if ($interval === 1) {
                return true;
            }

            return (int) $ref->startOfWeek()->diffInWeeks($date->copy()->startOfWeek()) % $interval === 0;
        }

        if ($type === 'monthly') {
            return $date->day === $ref->day &&
                   (int) $ref->diffInMonths($date) % $interval === 0;
        }

        if ($type === 'yearly') {
            return $date->month === $ref->month && $date->day === $ref->day
                && (int) $ref->diffInYears($date) % $interval === 0;
        }

        return false;
    }

    /**
     * Whether this group has a scheduled session on $date (same rules as status / QR availability).
     * Non-recurring: only when manually active. Recurring: not past recurrence_end_date and falls on a meeting day.
     */
    public function meetsOnDate(?Carbon $date = null): bool
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $date = ($date ?? Carbon::now($tz))->copy()->timezone($tz);

        $type = $this->attributes['recurrence_type'] ?? 'none';

        if ($type === 'none') {
            return (bool) ($this->attributes['active'] ?? false);
        }

        if ($this->recurrence_end_date) {
            $end = Carbon::parse($this->recurrence_end_date)->startOfDay();
            if ($date->copy()->startOfDay()->gt($end)) {
                return false;
            }
        }

        return $this->isTodayMeetingDay($date);
    }

    /**
     * Programa vigente (misma idea que admin "Activos"): activo manual o recurrente sin superar fecha de fin.
     */
    public function isProgramVigente(): bool
    {
        if ($this->attributes['active'] ?? false) {
            return true;
        }
        $type = $this->attributes['recurrence_type'] ?? 'none';
        if ($type === 'none') {
            return false;
        }
        if ($this->recurrence_end_date) {
            $end = Carbon::parse($this->recurrence_end_date)->startOfDay();

            return ! Carbon::now('America/Argentina/Buenos_Aires')->startOfDay()->gt($end);
        }

        return true;
    }

    /**
     * Programa cerrado (misma idea que admin "Finalizados").
     */
    public function isProgramClosed(): bool
    {
        $type = $this->attributes['recurrence_type'] ?? 'none';
        if ($type === 'none') {
            return ! ($this->attributes['active'] ?? false) && $this->getRawOriginal('started_at') != null;
        }
        if (! $this->recurrence_end_date) {
            return false;
        }

        return Carbon::now('America/Argentina/Buenos_Aires')->startOfDay()->gt(
            Carbon::parse($this->recurrence_end_date)->startOfDay()
        );
    }

    /**
     * Sin iniciar: no vigente ni cerrado (p. ej. grupo manual creado y aún no iniciado).
     */
    public function isProgramPending(): bool
    {
        if ($this->isProgramClosed() || $this->isProgramVigente()) {
            return false;
        }
        $type = $this->attributes['recurrence_type'] ?? 'none';
        if ($type === 'none') {
            return ! ($this->attributes['active'] ?? false) && $this->getRawOriginal('started_at') == null;
        }

        return $this->getRawOriginal('started_at') == null;
    }

    /** Days label for display: "Lun a Vie", "Lunes, Miércoles", or single day name */
    public function getMeetingDaysDisplayAttribute(): ?string
    {
        $days = $this->meeting_days ?? ($this->meeting_day ? [$this->meeting_day] : []);
        if (empty($days)) {
            return null;
        }

        return $this->formatDaysLabel($days);
    }

    public function getMeetingTimeFormattedAttribute(): ?string
    {
        if (! $this->meeting_time) {
            return null;
        }

        return date('H:i', strtotime($this->meeting_time));
    }

    /**
     * Human-readable recurrence label, e.g. "Semanal · Lun a Vie", "Cada 2 semanas · Lun, Mié", "Mensual"
     */
    public function getRecurrenceLabelAttribute(): string
    {
        $n = $this->recurrence_interval ?? 1;
        if (($this->attributes['recurrence_type'] ?? 'none') === 'weekly') {
            $days = $this->meeting_days ?? ($this->meeting_day ? [$this->meeting_day] : []);
            $prefix = $n > 1 ? "Cada {$n} semanas" : 'Semanal';

            return $prefix.(count($days) ? ' · '.$this->formatDaysLabel($days) : '');
        }

        return match ($this->attributes['recurrence_type'] ?? 'none') {
            'daily' => $n > 1 ? "Cada {$n} días" : 'Diario',
            'monthly' => $n > 1 ? "Cada {$n} meses" : 'Mensual',
            'yearly' => $n > 1 ? "Cada {$n} años" : 'Anual',
            default => 'Sin repetición',
        };
    }

    private function formatDaysLabel(array $days): string
    {
        $order = ['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6, 'Domingo' => 7];
        $abbr = ['Lunes' => 'Lun', 'Martes' => 'Mar', 'Miércoles' => 'Mié', 'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sábado' => 'Sáb', 'Domingo' => 'Dom'];
        usort($days, fn ($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
        if (count($days) === 1) {
            return $days[0];
        }
        $nums = array_map(fn ($d) => $order[$d] ?? 99, $days);
        $consecutive = true;
        for ($i = 1; $i < count($nums); $i++) {
            if ($nums[$i] - $nums[$i - 1] !== 1) {
                $consecutive = false;
                break;
            }
        }
        if ($consecutive) {
            return ($abbr[$days[0]] ?? $days[0]).' a '.($abbr[end($days)] ?? end($days));
        }

        return implode(', ', array_map(fn ($d) => $abbr[$d] ?? $d, $days));
    }

    public function getNextSessionAtAttribute(): ?Carbon
    {
        if (($this->attributes['recurrence_type'] ?? 'none') === 'none') {
            return null;
        }
        if (! $this->meeting_time) {
            return null;
        }

        $tz = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);
        [$hour, $minute] = explode(':', $this->meeting_time);

        switch ($this->attributes['recurrence_type']) {
            case 'daily':
                return $now->copy()->addDay()->setTime((int) $hour, (int) $minute);

            case 'weekly':
                $days = $this->meeting_days ?? ($this->meeting_day ? [$this->meeting_day] : []);
                if (empty($days)) {
                    return null;
                }
                $dayMap = [
                    'Domingo' => Carbon::SUNDAY,  'Lunes' => Carbon::MONDAY,
                    'Martes' => Carbon::TUESDAY, 'Miércoles' => Carbon::WEDNESDAY,
                    'Jueves' => Carbon::THURSDAY, 'Viernes' => Carbon::FRIDAY,
                    'Sábado' => Carbon::SATURDAY,
                ];
                $candidates = [];
                foreach ($days as $dayName) {
                    $targetDay = $dayMap[$dayName] ?? null;
                    if ($targetDay === null) {
                        continue;
                    }
                    if ($now->dayOfWeek === $targetDay) {
                        $today = $now->copy()->setTime((int) $hour, (int) $minute);
                        if ($now->lt($today)) {
                            $candidates[] = $today;

                            continue;
                        }
                    }
                    $candidates[] = $now->copy()->next($targetDay)->setTime((int) $hour, (int) $minute);
                }
                if (empty($candidates)) {
                    return null;
                }

                return collect($candidates)->sortBy(fn ($c) => $c->timestamp)->first();

            case 'monthly':
                return $now->copy()->addMonth()->setTime((int) $hour, (int) $minute);

            case 'yearly':
                return $now->copy()->addYear()->setTime((int) $hour, (int) $minute);
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

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function coordinators()
    {
        return $this->belongsToMany(User::class, 'group_coordinator');
    }

    public function patients()
    {
        return $this->belongsToMany(User::class, 'group_patient')->withPivot(
            'joined_at',
            'maintenance_weight',
            'join_source',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'first_device_user_agent',
        );
    }

    public function attendances()
    {
        return $this->hasMany(GroupAttendance::class);
    }

    public function weightRecords()
    {
        return $this->hasMany(WeightRecord::class);
    }
}
