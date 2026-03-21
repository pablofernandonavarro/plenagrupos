<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'meeting_day', 'meeting_time', 'auto_sessions', 'admin_id', 'qr_token', 'active', 'started_at', 'ended_at'];

    protected $casts = [
        'active'        => 'boolean',
        'auto_sessions' => 'boolean',
        'started_at'    => 'datetime',
        'ended_at'      => 'datetime',
    ];

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

    public function getNextSessionAtAttribute(): ?Carbon
    {
        if (!$this->auto_sessions || !$this->meeting_day || !$this->meeting_time) {
            return null;
        }

        $dayMap = [
            'Domingo' => Carbon::SUNDAY,    'Lunes'     => Carbon::MONDAY,
            'Martes'  => Carbon::TUESDAY,   'Miércoles' => Carbon::WEDNESDAY,
            'Jueves'  => Carbon::THURSDAY,  'Viernes'   => Carbon::FRIDAY,
            'Sábado'  => Carbon::SATURDAY,
        ];

        $targetDay = $dayMap[$this->meeting_day] ?? null;
        if ($targetDay === null) return null;

        [$hour, $minute] = explode(':', $this->meeting_time);
        $tz  = 'America/Argentina/Buenos_Aires';
        $now = Carbon::now($tz);

        // If today is the meeting day and the session hasn't started yet → show today
        if ($now->dayOfWeek === $targetDay) {
            $todaySession = $now->copy()->setTime((int) $hour, (int) $minute);
            if ($now->lt($todaySession)) {
                return $todaySession;
            }
        }

        return $now->next($targetDay)->setTime((int) $hour, (int) $minute);
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
        return $this->belongsToMany(User::class, 'group_patient')->withPivot('joined_at', 'maintenance_weight');
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
