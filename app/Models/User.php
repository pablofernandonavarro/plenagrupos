<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'ideal_weight',
        'peso_piso',
        'peso_techo',
        'role',
        'plan',
        'plan_start_date',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_start_date'   => 'date',
            'password'          => 'hashed',
        ];
    }

    /**
     * Returns [cycleStart, cycleEnd] for the patient's current 30-day billing period.
     * Falls back to the current calendar month if no plan_start_date is set.
     */
    public function currentPlanCycle(): array
    {
        if (!$this->plan_start_date) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        $start = $this->plan_start_date->copy();
        // Advance in 30-day increments until the next cycle start is in the future
        while ($start->copy()->addDays(30)->lte(now())) {
            $start->addDays(30);
        }

        return [$start->startOfDay(), $start->copy()->addDays(29)->endOfDay()];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function coordinatorGroups()
    {
        return $this->belongsToMany(Group::class, 'group_coordinator');
    }

    public function patientGroups()
    {
        return $this->belongsToMany(Group::class, 'group_patient')->withPivot('joined_at');
    }

    public function attendances()
    {
        return $this->hasMany(GroupAttendance::class);
    }

    public function weightRecords()
    {
        return $this->hasMany(WeightRecord::class);
    }

    public function inbodyRecords()
    {
        return $this->hasMany(InbodyRecord::class);
    }
}
