<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

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

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (User $user) {
            if (! $user->isForceDeleting()) {
                return;
            }
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            foreach ($user->inbodyRecords as $record) {
                if ($record->image_path) {
                    Storage::disk('public')->delete($record->image_path);
                }
            }
        });
    }

    /**
     * Returns [cycleStart, cycleEnd] for the patient's 30-day billing period containing $date
     * (defaults to now). Falls back to $date's calendar month if no plan_start_date is set.
     */
    public function currentPlanCycle(?Carbon $date = null): array
    {
        $date = $date ?? now();

        if (! $this->plan_start_date) {
            return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
        }

        $start = $this->plan_start_date->copy();
        // Advance in 30-day increments until the cycle containing $date is found
        while ($start->copy()->addDays(30)->lte($date)) {
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

    /**
     * true si la fase efectiva es mantenimiento (usa rango piso/techo).
     * false para descenso o fase sin definir (usa peso objetivo).
     */
    public function estaEnMantenimiento(): bool
    {
        return in_array($this->faseEfectiva(), ['mantenimiento', 'mantenimiento_pleno'], true);
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

    public function isMedico(): bool
    {
        return $this->role === 'medico';
    }

    public function isNutricionista(): bool
    {
        return $this->role === 'nutricionista';
    }

    public function isProfessional(): bool
    {
        return $this->isMedico() || $this->isNutricionista();
    }

    public function professionalSchedules()
    {
        return $this->hasMany(ProfessionalSchedule::class, 'professional_id');
    }

    public function professionalUnavailabilities()
    {
        return $this->hasMany(ProfessionalUnavailability::class, 'professional_id');
    }

    public function appointmentsAsPatient()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function appointmentsAsProfessional()
    {
        return $this->hasMany(Appointment::class, 'professional_id');
    }

    /**
     * Turnos completados/confirmados en el ciclo de 30 días vigente (currentPlanCycle), por especialidad.
     */
    public function turnosThisMonth(string $specialty): int
    {
        [$cycleStart, $cycleEnd] = $this->currentPlanCycle();

        return $this->appointmentsAsPatient()
            ->where('specialty', $specialty)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->count();
    }

    public function hasCompletedMonthlyRequirement(string $specialty): bool
    {
        return $this->turnosThisMonth($specialty) >= AppointmentRequirement::requiredCountFor($specialty);
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
