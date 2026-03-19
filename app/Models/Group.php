<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'meeting_day', 'meeting_time', 'admin_id', 'qr_token', 'active', 'started_at', 'ended_at'];

    protected $casts = [
        'active'     => 'boolean',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function getMeetingTimeFormattedAttribute(): ?string
    {
        if (!$this->meeting_time) return null;
        return date('H:i', strtotime($this->meeting_time));
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
