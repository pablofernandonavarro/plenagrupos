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
        'fase_actual',
        'plan_start_date',
        'patient_status',
        'patient_status_at',
        'patient_status_note',
        'belonging_group_id',
        'birth_date',
        'gender',
        'height_cm',
        'personal_goal',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'plan_start_date'    => 'date',
            'birth_date'         => 'date',
            'patient_status_at'  => 'datetime',
            'password'           => 'hashed',
        ];
    }

    /**
     * Returns [cycleStart, cycleEnd] for the patient's current 30-day billing period.
     * Falls back to the current calendar month if no plan_start_date is set.
     */
    public function currentPlanCycle(): array
    {
        if (! $this->plan_start_date) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        $start = $this->plan_start_date->copy();
        // Advance in 30-day increments until the next cycle start is in the future
        while ($start->copy()->addDays(30)->lte(now())) {
            $start->addDays(30);
        }

        return [$start->startOfDay(), $start->copy()->addDays(29)->endOfDay()];
    }

    /**
     * Fase efectiva: `fase_actual` o, si el coordinador no definió una, el plan contratado.
     * Se usa para límites de asistencia (PlanRule), vista del coordinador e informes (IA).
     * El ciclo de 30 días sigue usando `plan_start_date` / facturación.
     */
    public function faseEfectiva(): ?string
    {
        return $this->fase_actual ?? $this->plan;
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

    public function belongingGroup()
    {
        return $this->belongsTo(Group::class, 'belonging_group_id');
    }

    public function coordinatorGroups()
    {
        return $this->belongsToMany(Group::class, 'group_coordinator');
    }

    public function patientGroups()
    {
        return $this->belongsToMany(Group::class, 'group_patient')->withPivot(
            'joined_at',
            'left_at',
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

    public function inbodyRecords()
    {
        return $this->hasMany(InbodyRecord::class);
    }
}
